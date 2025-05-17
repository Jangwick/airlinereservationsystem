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
    <title>Baggage Information - SkyWay Airlines</title>
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
                    <h1><i class="fas fa-suitcase me-2"></i> Baggage Information</h1>
                    <p class="lead">Everything you need to know about baggage allowances, restrictions and special items.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-md-end mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>" class="text-white">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Baggage Information</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Quick Links -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Quick Links</h5>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-2">
                                <a href="#baggage-allowance" class="btn btn-outline-primary btn-sm d-block">Baggage Allowance</a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="#carry-on" class="btn btn-outline-primary btn-sm d-block">Carry-On Baggage</a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="#excess-baggage" class="btn btn-outline-primary btn-sm d-block">Excess Baggage</a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="#special-items" class="btn btn-outline-primary btn-sm d-block">Special Items</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Baggage Allowance Section -->
        <section id="baggage-allowance" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">Baggage Allowance</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Checked Baggage Allowance by Class</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Travel Class</th>
                                    <th>Economy</th>
                                    <th>Business</th>
                                    <th>First Class</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Domestic Routes</td>
                                    <td>20 kg</td>
                                    <td>30 kg</td>
                                    <td>40 kg</td>
                                </tr>
                                <tr>
                                    <td>International Short-haul</td>
                                    <td>25 kg</td>
                                    <td>35 kg</td>
                                    <td>45 kg</td>
                                </tr>
                                <tr>
                                    <td>International Long-haul</td>
                                    <td>30 kg</td>
                                    <td>40 kg</td>
                                    <td>50 kg</td>
                                </tr>
                                <tr>
                                    <td>Maximum Pieces</td>
                                    <td>2</td>
                                    <td>3</td>
                                    <td>3</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i> Infants without a seat are allowed 1 piece of checked baggage up to 10 kg plus a collapsible stroller.
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Size and Weight Restrictions</h5>
                </div>
                <div class="card-body">
                    <p>Each piece of checked baggage must not exceed:</p>
                    <ul>
                        <li><strong>Weight:</strong> 32 kg (70 lb) per piece</li>
                        <li><strong>Dimensions:</strong> The total dimensions (length + width + height) must not exceed 158 cm (62 inches)</li>
                    </ul>
                    <p>Items exceeding these limits will need to be shipped as cargo.</p>
                </div>
            </div>
        </section>
        
        <!-- Carry-On Baggage Section -->
        <section id="carry-on" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">Carry-On Baggage</h2>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Allowance by Class</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Travel Class</th>
                                            <th>Items</th>
                                            <th>Max Weight</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Economy</td>
                                            <td>1 bag + 1 personal item</td>
                                            <td>7 kg</td>
                                        </tr>
                                        <tr>
                                            <td>Business</td>
                                            <td>1 bag + 1 personal item</td>
                                            <td>10 kg</td>
                                        </tr>
                                        <tr>
                                            <td>First Class</td>
                                            <td>2 bags + 1 personal item</td>
                                            <td>14 kg</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Size Restrictions</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li><strong>Main cabin bag:</strong> 56 cm x 36 cm x 23 cm (22" x 14" x 9")</li>
                                <li><strong>Personal item:</strong> 40 cm x 30 cm x 15 cm (16" x 12" x 6")</li>
                            </ul>
                            <p>Personal items include:</p>
                            <ul>
                                <li>Handbag, purse, or small backpack</li>
                                <li>Laptop bag</li>
                                <li>Briefcase</li>
                                <li>Small camera bag</li>
                                <li>Diaper bag</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Restricted Items in Carry-On Baggage</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Liquids over 100 ml</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Sharp objects (knives, scissors with blades longer than 6 cm)</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Firearms and ammunition</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Flammable items</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Explosive materials</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Toxic substances</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Corrosive materials</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Self-defense equipment (pepper spray, stun guns)</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Lithium batteries not in a device</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Tools longer than 7 inches</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i> For a complete list of restricted and prohibited items, please refer to airport security regulations.
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Excess Baggage Section -->
        <section id="excess-baggage" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">Excess Baggage</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Excess Baggage Fees</h5>
                </div>
                <div class="card-body">
                    <p>If your baggage exceeds the free allowance, the following fees will apply per kg or per piece (depending on the route):</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Route</th>
                                    <th>Fee per Extra kg</th>
                                    <th>Fee per Extra Piece</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Domestic</td>
                                    <td>$10</td>
                                    <td>$50</td>
                                </tr>
                                <tr>
                                    <td>International Short-haul</td>
                                    <td>$15</td>
                                    <td>$75</td>
                                </tr>
                                <tr>
                                    <td>International Long-haul</td>
                                    <td>$20</td>
                                    <td>$100</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-success mt-3">
                        <h6><i class="fas fa-tag me-2"></i> Pre-purchase Discounts</h6>
                        <p class="mb-0">Save up to 40% by pre-purchasing excess baggage online at least 24 hours before your flight.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Special Items Section -->
        <section id="special-items" class="mb-5">
            <h2 class="mb-4 border-bottom pb-2">Special Items</h2>
            
            <div class="accordion shadow-sm" id="specialItemsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="sportingEquipment">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSporting" aria-expanded="true" aria-controls="collapseSporting">
                            <i class="fas fa-golf-ball me-2"></i> Sporting Equipment
                        </button>
                    </h2>
                    <div id="collapseSporting" class="accordion-collapse collapse show" aria-labelledby="sportingEquipment" data-bs-parent="#specialItemsAccordion">
                        <div class="accordion-body">
                            <p>The following sporting equipment may be accepted as part of your checked baggage allowance, subject to size and weight restrictions:</p>
                            <ul>
                                <li>Golf equipment</li>
                                <li>Ski and snowboard equipment</li>
                                <li>Fishing equipment</li>
                                <li>Bicycles (must be properly packed with handlebars fixed sideways and pedals removed)</li>
                                <li>Bowling equipment</li>
                            </ul>
                            <p>Oversized sporting equipment may be subject to additional handling fees.</p>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="musicalInstruments">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMusical" aria-expanded="false" aria-controls="collapseMusical">
                            <i class="fas fa-guitar me-2"></i> Musical Instruments
                        </button>
                    </h2>
                    <div id="collapseMusical" class="accordion-collapse collapse" aria-labelledby="musicalInstruments" data-bs-parent="#specialItemsAccordion">
                        <div class="accordion-body">
                            <p>Small musical instruments (violins, flutes, etc.) can be brought as carry-on luggage if they fit within the size restrictions. Larger instruments may need to be checked or may require the purchase of an extra seat.</p>
                            <p>We recommend properly packing all musical instruments in hard cases to prevent damage during transport.</p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Please contact our customer service at least 48 hours before your flight for assistance with transporting large musical instruments.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="medicalEquipment">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMedical" aria-expanded="false" aria-controls="collapseMedical">
                            <i class="fas fa-briefcase-medical me-2"></i> Medical Equipment
                        </button>
                    </h2>
                    <div id="collapseMedical" class="accordion-collapse collapse" aria-labelledby="medicalEquipment" data-bs-parent="#specialItemsAccordion">
                        <div class="accordion-body">
                            <p>Medical equipment necessary during flight (such as portable oxygen concentrators, nebulizers, CPAP machines) can be brought on board in addition to your regular carry-on allowance.</p>
                            <p>Important requirements:</p>
                            <ul>
                                <li>Equipment must comply with safety regulations</li>
                                <li>Battery-powered equipment must have sufficient battery life</li>
                                <li>Passengers must notify the airline at least 48 hours before departure</li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> Medical documentation may be required for certain equipment.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="fragileItems">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFragile" aria-expanded="false" aria-controls="collapseFragile">
                            <i class="fas fa-wine-glass me-2"></i> Fragile & Valuable Items
                        </button>
                    </h2>
                    <div id="collapseFragile" class="accordion-collapse collapse" aria-labelledby="fragileItems" data-bs-parent="#specialItemsAccordion">
                        <div class="accordion-body">
                            <p>We recommend carrying fragile or valuable items in your carry-on baggage when possible.</p>
                            <p>Items that should be kept in your carry-on include:</p>
                            <ul>
                                <li>Electronics (laptops, cameras, etc.)</li>
                                <li>Jewelry and watches</li>
                                <li>Important documents and cash</li>
                                <li>Medication</li>
                                <li>Delicate items that could break easily</li>
                            </ul>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> SkyWay Airlines is not liable for damage to fragile items or valuables packed in checked baggage.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Baggage Tips -->
        <section id="baggage-tips">
            <h2 class="mb-4 border-bottom pb-2">Baggage Tips</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-tag fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Label Your Luggage</h5>
                            <p class="card-text">Always attach a luggage tag with your name, contact information, and destination address to both inside and outside of each bag.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-clock fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Arrive Early</h5>
                            <p class="card-text">Check-in baggage at least 2 hours before domestic flights and 3 hours before international flights to avoid rush.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="fas fa-lock fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Secure Your Bags</h5>
                            <p class="card-text">Use TSA-approved locks to secure your checked bags while still allowing security screenings.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Call to Action -->
        <div class="bg-light p-4 rounded mt-5 text-center">
            <h4>Need Help with Baggage?</h4>
            <p>Our customer service team is ready to assist you with any baggage-related questions.</p>
            <a href="<?php echo $baseUrl; ?>pages/contact.php" class="btn btn-primary">
                <i class="fas fa-headset me-1"></i> Contact Us
            </a>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
