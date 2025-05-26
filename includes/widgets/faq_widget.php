<?php
/**
 * FAQ Widget
 * 
 * Displays a small FAQ section that can be included in other pages
 * Pass $category parameter to show FAQs from a specific category
 */

// Default to showing general booking FAQs if no category specified
$category = isset($category) ? $category : 'booking';

// Define basic FAQs by category
$faqs = [
    'booking' => [
        [
            'question' => 'How do I book a flight?',
            'answer' => 'You can book a flight through our website by using the search tool on our homepage. Enter your departure and arrival cities, dates, and number of passengers, then follow the booking process.'
        ],
        [
            'question' => 'Can I book a flight for someone else?',
            'answer' => 'Yes, you can book a flight for someone else. During the booking process, you\'ll need to enter the passenger\'s details including their full name as it appears on their ID/passport.'
        ]
    ],
    'check-in' => [
        [
            'question' => 'When does online check-in open?',
            'answer' => 'Online check-in opens 48 hours before flight departure and closes 1 hour before departure for domestic flights and 2 hours for international flights.'
        ],
        [
            'question' => 'How do I check in online?',
            'answer' => 'You can check in online through our website\'s "Web Check-in" section or through our mobile app using your booking reference number and last name.'
        ]
    ],
    'baggage' => [
        [
            'question' => 'What is the baggage allowance?',
            'answer' => 'Baggage allowance varies depending on your ticket class and destination. Generally, economy class passengers are allowed one carry-on bag (max. 7kg) and one checked bag (max. 23kg).'
        ],
        [
            'question' => 'Can I purchase additional baggage allowance?',
            'answer' => 'Yes, you can purchase additional baggage allowance through our website under "Manage Booking" section or by contacting our customer service.'
        ]
    ],
];

// Get FAQs for the requested category
$category_faqs = isset($faqs[$category]) ? $faqs[$category] : $faqs['booking'];

// Generate a unique ID for this widget instance
$widget_id = 'faqWidget_' . uniqid();

// Determine the base URL for links
$baseUrl = '';
if (function_exists('getBaseUrl')) {
    $baseUrl = getBaseUrl();
} else {
    // Simple fallback if getBaseUrl doesn't exist
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . '/airlinereservationsystem/';
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Frequently Asked Questions</h5>
            <a href="<?php echo $baseUrl; ?>pages/faq.php?category=<?php echo $category; ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
    <div class="card-body">
        <div class="accordion" id="<?php echo $widget_id; ?>">
            <?php foreach($category_faqs as $index => $faq): ?>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="heading<?php echo $index; ?>_<?php echo $widget_id; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $index; ?>_<?php echo $widget_id; ?>" 
                                aria-expanded="false" 
                                aria-controls="collapse<?php echo $index; ?>_<?php echo $widget_id; ?>">
                            <?php echo htmlspecialchars($faq['question']); ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $index; ?>_<?php echo $widget_id; ?>" 
                         class="accordion-collapse collapse" 
                         aria-labelledby="heading<?php echo $index; ?>_<?php echo $widget_id; ?>" 
                         data-bs-parent="#<?php echo $widget_id; ?>">
                        <div class="accordion-body">
                            <?php echo htmlspecialchars($faq['answer']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-footer bg-white text-center border-0">
        <a href="<?php echo $baseUrl; ?>pages/faq.php" class="text-decoration-none">
            <i class="fas fa-question-circle me-1"></i> Need more help? View our complete FAQ
        </a>
    </div>
</div>
