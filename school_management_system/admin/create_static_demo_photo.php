<?php
/**
 * Create Static Demo Photo - No GD Extension Required
 * This script creates a simple placeholder demo photo using minimal binary data
 */

// Directory setup
$upload_dir = __DIR__ . '/../uploads/students/';
$thumb_dir = $upload_dir . 'thumbnails/';

// Ensure directories exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "Created upload directory: $upload_dir\n";
}

if (!file_exists($thumb_dir)) {
    mkdir($thumb_dir, 0755, true);
    echo "Created thumbnail directory: $thumb_dir\n";
}

// Create a minimal valid JPEG file (1x1 pixel placeholder)
function createMinimalJPEG($filename) {
    // This is a minimal valid JPEG file (1x1 pixel, grayscale)
    $jpeg_data = hex2bin(
        'ffd8ffe000104a46494600010101004800480000ffdb00430001010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101ffdb00430101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101ffc20011080001000103011100021101031101ffc4001400000105010100000000000000000000000000080affc40014100100000000000000000000000000000000ffc40014010100000000000000000000000000000000ffc40014110100000000000000000000000000000000ffda000c03010002110311003f00d2cf20ffd9'
    );
    
    file_put_contents($filename, $jpeg_data);
    return file_exists($filename);
}

// Create demo photos
$demo_files = [
    'demo_student_1.jpg',
    'demo_student_2.jpg', 
    'demo_student_3.jpg'
];

$success_count = 0;

foreach ($demo_files as $filename) {
    $filepath = $upload_dir . $filename;
    $thumbpath = $thumb_dir . $filename;
    
    if (createMinimalJPEG($filepath)) {
        // Copy to thumbnail directory
        if (copy($filepath, $thumbpath)) {
            echo "✅ Created demo photo: $filename\n";
            echo "   - Main file: $filepath\n";
            echo "   - Thumbnail: $thumbpath\n\n";
            $success_count++;
        } else {
            echo "❌ Failed to create thumbnail for: $filename\n";
        }
    } else {
        echo "❌ Failed to create demo photo: $filename\n";
    }
}

if ($success_count > 0) {
    echo "\n🎉 Successfully created $success_count demo photo(s)!\n";
    echo "\n📝 USAGE INSTRUCTIONS:\n";
    echo "1. Go to students_add.php\n";
    echo "2. Click 'Fill Demo Data' button\n";
    echo "3. Manually select one of the created demo photos from the upload directory\n";
    echo "4. Submit the form to test photo upload functionality\n";
    echo "\n💡 Note: The demo photos are minimal 1x1 pixel placeholders.\n";
    echo "   For better testing, replace them with actual demo images.\n";
    
    // Also create a simple HTML file to help with testing
    $test_html = '<!DOCTYPE html>
<html>
<head>
    <title>Demo Photo Test Helper</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .info { background: #e8f4f8; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; }
    </style>
</head>
<body>
    <h1>Demo Photo Test Helper</h1>
    
    <div class="success">
        <strong>✅ Demo photos created successfully!</strong>
    </div>
    
    <div class="info">
        <h3>📁 Created Files:</h3>
        <ul>';
    
    foreach ($demo_files as $filename) {
        $test_html .= '<li>' . $filename . '</li>';
    }
    
    $test_html .= '</ul>
        
        <h3>🧪 Testing Steps:</h3>
        <ol>
            <li><a href="students_add.php" target="_blank">Open Students Add Form</a></li>
            <li>Click the "🧪 Fill Demo Data" button</li>
            <li>In the photo upload field, browse and select one of the demo photos</li>
            <li>Submit the form</li>
            <li>Check if the student is successfully added with photo</li>
        </ol>
        
        <h3>🔍 Debugging:</h3>
        <p>If issues occur, check the PHP error log for detailed information about:</p>
        <ul>
            <li>Photo upload errors</li>
            <li>Database insertion errors</li>
            <li>File permission issues</li>
        </ul>
        
        <p><strong>PHP Error Log Location:</strong> Check your XAMPP/PHP configuration for the error log path.</p>
    </div>
</body>
</html>';
    
    file_put_contents(__DIR__ . '/demo_photo_test_helper.html', $test_html);
    echo "\n📄 Created test helper file: demo_photo_test_helper.html\n";
    echo "   Open this file in your browser for testing instructions.\n";
    
} else {
    echo "\n❌ Failed to create any demo photos. Please check file permissions.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Demo Photo Creation Complete\n";
echo str_repeat("=", 60) . "\n";
?>
