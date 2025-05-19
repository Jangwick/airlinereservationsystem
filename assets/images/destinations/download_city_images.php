<?php
// List of specific cities needed with tailored search terms for quality results
$cities = [
    'manila' => 'manila philippines skyline city',
    'singapore' => 'singapore marina bay skyline',
    'cebu' => 'cebu philippines beach resort',
    'dubai' => 'dubai burj khalifa skyline',
    'tokyo' => 'tokyo japan skyline shinjuku',
    'seoul' => 'seoul south korea skyline night'
];

// Directory to save images
$directory = __DIR__;
if (!file_exists($directory)) {
    mkdir($directory, 0755, true);
}

// Function to get an image from Unsplash with specific search parameters
function getUnsplashImage($searchTerm) {
    // Format: 1600x900 landscape-oriented images
    $searchTerm = urlencode($searchTerm);
    $url = "https://source.unsplash.com/featured/1600x900/?" . $searchTerm;
    
    echo "Fetching from: " . $url . "<br>";
    
    // Get image content with proper user agent
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ];
    
    $context = stream_context_create($opts);
    $imageContent = file_get_contents($url, false, $context);
    
    if ($imageContent === false) {
        return false;
    }
    
    return $imageContent;
}

// Download images for each city
$results = [];
foreach ($cities as $city => $searchTerm) {
    echo "<h3>Downloading image for $city...</h3>";
    
    // Try up to 3 times to get a good image
    $maxAttempts = 3;
    $attempt = 1;
    $success = false;
    
    while (!$success && $attempt <= $maxAttempts) {
        echo "Attempt $attempt of $maxAttempts<br>";
        
        $imageContent = getUnsplashImage($searchTerm);
        
        if ($imageContent) {
            $filePath = $directory . '/' . strtolower($city) . '.jpg';
            
            // Save the image
            if (file_put_contents($filePath, $imageContent)) {
                $success = true;
                $results[$city] = [
                    'status' => 'success',
                    'path' => $filePath,
                    'attempt' => $attempt
                ];
                
                echo "<div style='color:green; font-weight:bold;'>✅ Successfully downloaded image for $city</div><br>";
            } else {
                echo "Failed to save image for $city<br>";
            }
        } else {
            echo "Failed to download image for $city<br>";
        }
        
        $attempt++;
        
        // Add a small delay between attempts
        if (!$success && $attempt <= $maxAttempts) {
            echo "Waiting before next attempt...<br>";
            sleep(1);
        }
    }
    
    if (!$success) {
        $results[$city] = [
            'status' => 'error',
            'message' => 'Failed to download image after ' . $maxAttempts . ' attempts'
        ];
        
        echo "<div style='color:red; font-weight:bold;'>❌ Failed to download image for $city after $maxAttempts attempts</div><br>";
    }
    
    echo "<hr>";
}

// Create or update a default image as fallback
echo "<h3>Creating default image...</h3>";
$defaultImagePath = $directory . '/default.jpg';

// Check if default image exists already
if (!file_exists($defaultImagePath)) {
    require_once 'create_default_image.php';
} else {
    echo "Default image already exists at: $defaultImagePath<br>";
}

// Generate output with thumbnails
echo "<h2>Downloaded Destination Images</h2>";
echo "<div style='display: flex; flex-wrap: wrap;'>";
foreach ($cities as $city => $searchTerm) {
    $filePath = $directory . '/' . strtolower($city) . '.jpg';
    if (file_exists($filePath)) {
        echo "<div style='margin: 10px; text-align: center;'>";
        echo "<img src='" . strtolower($city) . ".jpg' width='250' height='140' style='object-fit: cover;'><br>";
        echo "<strong>" . ucfirst($city) . "</strong><br>";
        echo "File: " . strtolower($city) . ".jpg";
        echo "</div>";
    }
}
echo "</div>";

// Show default image
echo "<div style='margin: 10px; text-align: center;'>";
echo "<img src='default.jpg' width='250' height='140' style='object-fit: cover;'><br>";
echo "<strong>Default Image</strong><br>";
echo "File: default.jpg";
echo "</div>";

// Generate PHP code for city image mapping
echo "<h3>PHP Code for City Image Array:</h3>";
echo "<pre>";
echo "// City image mapping for flight search\n";
echo "\$cityImages = [\n";
foreach ($cities as $city => $searchTerm) {
    echo "    '" . strtolower($city) . "' => '" . strtolower($city) . ".jpg',\n";
}
echo "];\n\n";
echo "// Example usage in search.php:\n";
echo "function getCityImage(\$city, \$cityImages, \$baseUrl) {\n";
echo "    \$city = strtolower(\$city);\n";
echo "    \n";
echo "    if (isset(\$cityImages[\$city])) {\n";
echo "        return \$baseUrl . 'assets/images/destinations/' . \$cityImages[\$city];\n";
echo "    }\n";
echo "    \n";
echo "    return \$baseUrl . 'assets/images/destinations/default.jpg';\n";
echo "}\n";
echo "</pre>";
?>

<h3>Manual Testing</h3>
<p>Open each link below to verify images are properly downloaded:</p>
<ul>
<?php foreach ($cities as $city => $searchTerm): ?>
    <li><a href="<?= strtolower($city) ?>.jpg" target="_blank"><?= ucfirst($city) ?></a></li>
<?php endforeach; ?>
    <li><a href="default.jpg" target="_blank">Default Image</a></li>
</ul>
