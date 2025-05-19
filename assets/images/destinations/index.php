<?php
// Check if the default.jpg is being requested
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'default.jpg') !== false) {
    // Set the content type to JPEG image
    header('Content-Type: image/jpeg');
    
    // If the file exists, serve it
    if (file_exists(__DIR__ . '/default.jpg')) {
        readfile(__DIR__ . '/default.jpg');
        exit;
    }
    
    // Otherwise, create a basic placeholder image
    $width = 600;
    $height = 400;
    
    // Create the image
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bg = imagecolorallocate($image, 240, 240, 240);    // Light gray background
    $text = imagecolorallocate($image, 80, 80, 80);     // Dark gray text
    
    // Fill background
    imagefill($image, 0, 0, $bg);
    
    // Add text
    $message = "Destination Image Placeholder";
    imagestring($image, 5, $width/2 - 100, $height/2 - 10, $message, $text);
    
    // Output image
    imagejpeg($image);
    imagedestroy($image);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Default Image</title>
</head>
<body>
    <h1>Default Image Creator</h1>
    <p>This script will automatically create the default.jpg when it's requested.</p>
    <p>If you're seeing this page, it means you accessed the directory directly instead of requesting the image.</p>
    
    <h2>Manual Creation</h2>
    <p>Click the button below to manually create the default image:</p>
    <form method="post">
        <button type="submit" name="create">Create Default Image</button>
    </form>
    
    <?php
    if (isset($_POST['create'])) {
        $dir = __DIR__;
        $file = $dir . '/default.jpg';
        
        // Create the image
        $width = 600;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 240, 240, 240);
        $text = imagecolorallocate($image, 80, 80, 80);
        imagefill($image, 0, 0, $bg);
        $message = "Destination Image Placeholder";
        imagestring($image, 5, $width/2 - 100, $height/2 - 10, $message, $text);
        
        if (imagejpeg($image, $file)) {
            echo "<p style='color:green'>Default image created successfully at: $file</p>";
        } else {
            echo "<p style='color:red'>Failed to create default image.</p>";
        }
        imagedestroy($image);
    }
    ?>
</body>
</html>
