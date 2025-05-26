<?php
session_start();

// Include database connection
require_once '../db/db_config.php';

// Initialize FAQ categories array
$faq_categories = [
    'booking' => 'Booking & Reservations',
    'flights' => 'Flights & Schedule',
    'baggage' => 'Baggage Information',
    'check-in' => 'Check-in & Boarding',
    'payment' => 'Payment & Pricing',
    'cancellation' => 'Cancellations & Refunds',
    'loyalty' => 'Loyalty Program',
    'travel' => 'Travel Guides & Tips' // Add this new category
];

// Initialize FAQs array
$faqs = [
    'booking' => [
        [
            'question' => 'How do I book a flight?',
            'answer' => 'You can book a flight through our website by using the search tool on our homepage. Enter your departure and arrival cities, dates, and number of passengers. Browse through the available options and select the flight that best suits your needs. Follow the booking process, provide passenger details, and make payment to confirm your reservation.'
        ],
        [
            'question' => 'Can I book a flight for someone else?',
            'answer' => 'Yes, you can book a flight for someone else. During the booking process, you\'ll need to enter the passenger\'s details including their full name as it appears on their ID/passport, date of birth, and contact information.'
        ],
        [
            'question' => 'How can I view or manage my booking?',
            'answer' => 'You can view and manage your booking by logging into your account and navigating to "My Bookings" section. Alternatively, you can use the "Manage Booking" option on our homepage by entering your booking reference number and last name.'
        ],
        [
            'question' => 'Can I select my seat during booking?',
            'answer' => 'Yes, seat selection is available during the booking process after you select your flight. Some seats may incur additional charges, especially those with extra legroom or in premium cabin locations.'
        ]
    ],
    'flights' => [
        [
            'question' => 'What happens if my flight is delayed or cancelled?',
            'answer' => 'If your flight is delayed or cancelled, we will notify you via email and/or SMS if you provided your contact details. You will be offered rebooking options on the next available flight or a refund depending on the circumstances.'
        ],
        [
            'question' => 'How early should I arrive at the airport?',
            'answer' => 'For domestic flights, we recommend arriving at least 2 hours before departure. For international flights, please arrive 3 hours before departure to allow sufficient time for check-in, security screening, and immigration procedures.'
        ],
        [
            'question' => 'Do you offer special assistance for passengers with disabilities?',
            'answer' => 'Yes, we offer special assistance for passengers with disabilities or reduced mobility. Please inform us about any special requirements during booking or contact our customer service at least 48 hours before your flight.'
        ]
    ],
    'baggage' => [
        [
            'question' => 'What is the baggage allowance?',
            'answer' => 'Baggage allowance varies depending on your ticket class and destination. Generally, economy class passengers are allowed one carry-on bag (max. 7kg) and one checked bag (max. 23kg). Business and First Class passengers are allowed additional or heavier baggage. Please check your specific flight details for accurate information.'
        ],
        [
            'question' => 'What items are prohibited in checked luggage?',
            'answer' => 'Prohibited items in checked luggage include but are not limited to: flammable substances, explosives, compressed gases, toxic substances, corrosive materials, certain electronic devices with lithium batteries, and valuable items like cash, jewelry, or important documents.'
        ],
        [
            'question' => 'Can I purchase additional baggage allowance?',
            'answer' => 'Yes, you can purchase additional baggage allowance through our website under "Manage Booking" section or by contacting our customer service. It\'s more economical to purchase extra baggage in advance rather than at the airport.'
        ]
    ],
    'check-in' => [
        [
            'question' => 'When does online check-in open?',
            'answer' => 'Online check-in opens 48 hours before flight departure and closes 1 hour before departure for domestic flights and 2 hours for international flights.'
        ],
        [
            'question' => 'How do I check in online?',
            'answer' => 'You can check in online through our website\'s "Web Check-in" section or through our mobile app. You\'ll need your booking reference number and the last name of the passenger.'
        ],
        [
            'question' => 'What documents do I need for check-in?',
            'answer' => 'For domestic flights, you\'ll need a government-issued photo ID. For international flights, you\'ll need a valid passport (with minimum 6 months validity) and visa documentation if required for your destination.'
        ]
    ],
    'payment' => [
        [
            'question' => 'What payment methods do you accept?',
            'answer' => 'We accept various payment methods including major credit/debit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for certain bookings. All payments are processed securely.'
        ],
        [
            'question' => 'Is my payment information secure?',
            'answer' => 'Yes, all payment information is processed through secure, encrypted connections. We comply with PCI DSS (Payment Card Industry Data Security Standard) to ensure your payment details are protected.'
        ],
        [
            'question' => 'When will my credit card be charged?',
            'answer' => 'Your credit card will be charged immediately upon confirmation of your booking. For certain promotional fares, the full amount is charged at the time of booking.'
        ]
    ],
    'cancellation' => [
        [
            'question' => 'How do I cancel my flight?',
            'answer' => 'You can cancel your flight through the "My Bookings" section of your account or through the "Manage Booking" option on our homepage. Enter your booking reference and last name, then select the cancellation option.'
        ],
        [
            'question' => 'What is your refund policy?',
            'answer' => 'Refund policies vary depending on fare type and timing of cancellation. Non-refundable tickets may not qualify for a refund but might be eligible for a partial refund of taxes and fees. Refundable tickets are subject to cancellation fees which vary based on how close to the departure date you cancel. Please refer to your ticket\'s terms and conditions for specific details.'
        ],
        [
            'question' => 'How long does it take to process a refund?',
            'answer' => 'Once a refund is approved, it typically takes 7-10 business days for the amount to be credited back to your original payment method. However, it may take longer depending on your bank or credit card issuer.'
        ]
    ],
    'loyalty' => [
        [
            'question' => 'How do I join your loyalty program?',
            'answer' => 'You can join our SkyWay Miles loyalty program by registering on our website\'s "Loyalty Program" section. Registration is free and you can start earning miles on your very next flight.'
        ],
        [
            'question' => 'How do I earn and redeem miles?',
            'answer' => 'You earn miles based on the distance flown and fare class. Miles can be redeemed for free flights, upgrades, excess baggage allowance, and various partner services including hotels and car rentals. Log in to your loyalty account to check your miles balance and redemption options.'
        ],
        [
            'question' => 'Do miles expire?',
            'answer' => 'Yes, miles generally expire after 24 months of inactivity in your account. Any earning or redeeming activity will reset the expiration clock.'
        ]
    ],
    'travel' => [
        [
            'question' => 'What are the visa requirements for international travel?',
            'answer' => 'Visa requirements vary by country and your nationality. We recommend checking with the embassy or consulate of your destination country at least 3-4 weeks before travel. For many destinations, you may need a passport valid for at least 6 months beyond your stay. Some countries also require proof of onward travel and sufficient funds.'
        ],
        [
            'question' => 'What should I pack for my flight?',
            'answer' => 'Essential items include your travel documents (passport, ID, boarding pass), prescription medications, a change of clothes, toiletries (under 100ml for carry-on), electronic devices and chargers, snacks, and entertainment. For long flights, consider neck pillows, eye masks, and compression socks for comfort.'
        ],
        [
            'question' => 'How early should I arrive at the airport?',
            'answer' => 'For domestic flights, arrive 2 hours before departure. For international flights, arrive 3 hours before departure. During peak travel seasons or at busy airports, consider adding an additional 30 minutes.'
        ],
        [
            'question' => 'What are the best restaurants and attractions in popular destinations?',
            'answer' => 'We provide city guides for our most popular destinations with recommendations for dining, attractions, and local experiences. Check the travel guides section of our website or ask our customer service team for personalized recommendations based on your interests.'
        ]
    ]
];

$current_category = isset($_GET['category']) && array_key_exists($_GET['category'], $faq_categories) ? $_GET['category'] : 'booking';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// If there's a search term, filter FAQs
if (!empty($search_term)) {
    $search_results = [];
    foreach ($faqs as $category => $questions) {
        foreach ($questions as $qa) {
            if (stripos($qa['question'], $search_term) !== false || stripos($qa['answer'], $search_term) !== false) {
                $qa['category'] = $category;
                $search_results[] = $qa;
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
    <title>Frequently Asked Questions - SkyWay Airlines</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            padding: 5rem 0;
            color: white;
            margin-bottom: 2rem;
        }
        
        .category-nav .nav-link {
            color: #495057;
            border-radius: 0;
            padding: .75rem 1rem;
            background-color: #f8f9fa;
        }
        
        .category-nav .nav-link.active {
            background-color: #0d6efd;
            color: white;
            border-left: 4px solid #0a58ca;
        }
        
        .category-nav .nav-link:hover:not(.active) {
            background-color: #e9ecef;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
            color: #0d6efd;
        }
        
        .faq-search {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .faq-search .form-control {
            padding-right: 50px;
            border-radius: 50px;
            height: 50px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .faq-search .btn {
            position: absolute;
            right: 4px;
            top: 4px;
            border-radius: 50px;
            height: 42px;
            width: 42px;
            padding: 0;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Frequently Asked Questions</h1>
                    <p class="lead mb-5">Find answers to commonly asked questions about our services, bookings, flights, and more.</p>
                    
                    <!-- Search Form -->
                    <form action="faq.php" method="get" class="faq-search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search for answers..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-light" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="container mb-5">
        <?php if (!empty($search_term) && isset($search_results)): ?>
            <!-- Search Results -->
            <div class="mb-4">
                <h2>Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h2>
                <p>Found <?php echo count($search_results); ?> results</p>
                <a href="faq.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to All FAQs
                </a>
                
                <?php if (count($search_results) > 0): ?>
                    <div class="accordion" id="searchResultsAccordion">
                        <?php foreach ($search_results as $index => $qa): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="searchHeading<?php echo $index; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#searchCollapse<?php echo $index; ?>" aria-expanded="false" 
                                            aria-controls="searchCollapse<?php echo $index; ?>">
                                        <?php 
                                        $highlighted_question = preg_replace('/(' . preg_quote($search_term, '/') . ')/i', '<span class="highlight">$1</span>', htmlspecialchars($qa['question']));
                                        echo $highlighted_question; 
                                        ?>
                                        <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($faq_categories[$qa['category']]); ?></span>
                                    </button>
                                </h2>
                                <div id="searchCollapse<?php echo $index; ?>" class="accordion-collapse collapse" 
                                     aria-labelledby="searchHeading<?php echo $index; ?>" data-bs-parent="#searchResultsAccordion">
                                    <div class="accordion-body">
                                        <?php 
                                        $highlighted_answer = preg_replace('/(' . preg_quote($search_term, '/') . ')/i', '<span class="highlight">$1</span>', htmlspecialchars($qa['answer']));
                                        echo $highlighted_answer; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No results found. Please try another search term.
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Regular FAQ View -->
            <div class="row">
                <!-- Category Navigation -->
                <div class="col-lg-3 mb-4">
                    <div class="sticky-lg-top" style="top: 100px;">
                        <h5 class="mb-3">Categories</h5>
                        <div class="list-group category-nav mb-4">
                            <?php foreach ($faq_categories as $cat_key => $cat_name): ?>
                                <a href="?category=<?php echo $cat_key; ?>" class="list-group-item list-group-item-action <?php echo $cat_key === $current_category ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat_name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="card border-primary mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Need more help?</h5>
                                <p class="card-text">If you couldn't find your answer here, please contact our customer service team.</p>
                                <a href="contact.php" class="btn btn-primary">
                                    <i class="fas fa-headset me-1"></i> Contact Us
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ Accordion -->
                <div class="col-lg-9">
                    <h2 class="mb-4"><?php echo htmlspecialchars($faq_categories[$current_category]); ?></h2>
                    
                    <div class="accordion" id="faqAccordion">
                        <?php if (isset($faqs[$current_category])): ?>
                            <?php foreach ($faqs[$current_category] as $index => $qa): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" 
                                                aria-controls="collapse<?php echo $index; ?>">
                                            <?php echo htmlspecialchars($qa['question']); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" 
                                         aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <?php echo htmlspecialchars($qa['answer']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No FAQs found for this category.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Call to Action Section -->
    <section class="bg-light py-5 mb-0">
        <div class="container text-center">
            <h3>Didn't find what you're looking for?</h3>
            <p class="lead mb-4">Our customer support team is here to help you 24/7.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="contact.php" class="btn btn-primary">Contact Support</a>
                <a href="tel:+123456789" class="btn btn-outline-primary">
                    <i class="fas fa-phone me-1"></i> Call Us
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Open the first FAQ if no search is active
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            
            // If a search param exists, open all results
            if (searchParam) {
                document.querySelectorAll('.accordion-button').forEach(button => {
                    button.click();
                });
            }
            
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                        
                        // If it's a FAQ item, expand it
                        const accordionButton = targetElement.querySelector('.accordion-button');
                        if (accordionButton && accordionButton.classList.contains('collapsed')) {
                            accordionButton.click();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
