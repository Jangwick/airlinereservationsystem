<?php
/**
 * City Guides Widget
 * 
 * Displays city guides with links to FAQs about specific destinations
 */
 
// Define popular cities with small descriptions
$popular_cities = [
    [
        'city' => 'New York',
        'image' => '../assets/images/cities/new-york.jpg', // Make sure these images exist
        'description' => 'The Big Apple - a global center for media, culture, fashion, and finance.',
        'faq_link' => '../pages/faq.php?search=new+york'
    ],
    [
        'city' => 'London',
        'image' => '../assets/images/cities/london.jpg',
        'description' => 'Historic architecture meets modern culture in England\'s vibrant capital city.',
        'faq_link' => '../pages/faq.php?search=london'
    ],
    [
        'city' => 'Tokyo',
        'image' => '../assets/images/cities/tokyo.jpg',
        'description' => 'Japan\'s busy capital mixes ultra-modern with traditional, from neon-lit skyscrapers to historic temples.',
        'faq_link' => '../pages/faq.php?search=tokyo'
    ],
    [
        'city' => 'Dubai',
        'image' => '../assets/images/cities/dubai.jpg',
        'description' => 'Known for luxury shopping, ultramodern architecture, and a lively nightlife scene.',
        'faq_link' => '../pages/faq.php?search=dubai'
    ]
];

// Get base URL for correct paths
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/airlinereservationsystem/';
    }
}

$baseUrl = isset($baseUrl) ? $baseUrl : getBaseUrl();

// Generate a unique ID for this widget instance
$widget_id = 'cityGuides_' . uniqid();
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Popular Destinations</h5>
            <a href="<?php echo $baseUrl; ?>pages/destinations.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach($popular_cities as $index => $city): ?>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="position-relative">
                            <div class="ratio ratio-16x9">
                                <img src="<?php echo $baseUrl . 'assets/images/placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($city['city']); ?>" onerror="this.src='<?php echo $baseUrl; ?>assets/images/placeholder.jpg'">
                            </div>
                            <div class="position-absolute top-0 start-0 p-2">
                                <h6 class="badge bg-primary"><?php echo htmlspecialchars($city['city']); ?></h6>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text small"><?php echo htmlspecialchars($city['description']); ?></p>
                            <a href="<?php echo $city['faq_link']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-question-circle me-1"></i> Travel Tips & FAQs
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-footer bg-white text-center border-0">
        <a href="<?php echo $baseUrl; ?>pages/faq.php?category=travel" class="text-decoration-none">
            <i class="fas fa-plane me-1"></i> View more travel guides and FAQs
        </a>
    </div>
</div>
