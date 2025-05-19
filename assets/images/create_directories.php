<?php
// Create directory structure for destination images
$destinationsDir = __DIR__ . '/destinations';

// Create the directory if it doesn't exist
if (!file_exists($destinationsDir)) {
    if (mkdir($destinationsDir, 0755, true)) {
        echo "Destinations directory created successfully.<br>";
    } else {
        echo "Failed to create destinations directory.<br>";
        exit;
    }
} else {
    echo "Destinations directory already exists.<br>";
}

echo "Directory setup complete!";
?>
