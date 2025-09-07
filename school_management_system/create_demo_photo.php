<?php
/**
 * Create Demo Photos for Testing
 */

function createDemoPhoto($name = 'Demo Student', $color = null) {
    $upload_dir = __DIR__ . '/uploads/students/';
    $thumb_dir = $upload_dir . 'thumbnails/';
    
    // Ensure directories exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (!file_exists($thumb_dir)) {
        mkdir($thumb_dir, 0755, true);
    }
    
    // Create a demo image
    $width = 300;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);
    
    // Random colors if not specified
    if (!$color) {
        $colors = [
            [100, 150, 200], // Blue
            [150, 100, 200], // Purple
            [100, 200, 150], // Green
            [200, 150, 100], // Orange
            [200, 100, 150], // Pink
        ];
        $color = $colors[array_rand($colors)];
    }
    
    // Colors
    $bg_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $white = imagecolorallocate($image, 255, 255, 255);
    $dark = imagecolorallocate($image, 50, 50, 50);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Add a circle for face
    $center_x = $width / 2;
    $center_y = $height / 2 - 20;
    $face_radius = 80;
    imagefilledellipse($image, $center_x, $center_y, $face_radius * 2, $face_radius * 2, $white);
    
    // Add eyes
    imagefilledellipse($image, $center_x - 25, $center_y - 20, 10, 15, $dark);
    imagefilledellipse($image, $center_x + 25, $center_y - 20, 10, 15, $dark);
    
    // Add smile
    imagearc($image, $center_x, $center_y - 10, 50, 30, 0, 180, $dark);
    
    // Add name text
    $name_parts = explode(' ', $name);
    $short_name = count($name_parts) > 1 ? 
        strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1)) : 
        strtoupper(substr($name, 0, 2));
    
    // Add initials
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * strlen($short_name);
    $text_x = ($width - $text_width) / 2;
    $text_y = $height - 40;
    
    imagestring($image, $font_size, $text_x, $text_y, $short_name, $white);
    
    // Add "DEMO" text
    $demo_text = "DEMO";
    $demo_width = imagefontwidth(3) * strlen($demo_text);
    $demo_x = ($width - $demo_width) / 2;
    $demo_y = $height - 20;
    
    imagestring($image, 3, $demo_x, $demo_y, $demo_text, $white);
    
    // Save demo photo
    $filename = 'demo_' . strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.jpg';
    $filepath = $upload_dir . $filename;
    $thumb_filepath = $thumb_dir . $filename;
    
    // Save original
    imagejpeg($image, $filepath, 90);
    
    // Create thumbnail (200x200)
    $thumb = imagecreatetruecolor(200, 200);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, 200, 200, $width, $height);
    imagejpeg($thumb, $thumb_filepath, 90);
    
    // Clean up
    imagedestroy($image);
    imagedestroy($thumb);
    
    return [
        'filename' => $filename,
        'path' => $filepath,
        'thumb_path' => $thumb_filepath,
        'url' => 'uploads/students/' . $filename,
        'thumb_url' => 'uploads/students/thumbnails/' . $filename
    ];
}

// Create demo photos
echo "<h1>Creating Demo Photos...</h1>";
echo "<style>body{font-family:Arial;margin:40px;} img{margin:10px;border:2px solid #ccc;}</style>";

$demo_photos = [
    createDemoPhoto('Priya Sharma', [100, 150, 200]),
    createDemoPhoto('Rahul Kumar', [150, 100, 200]),
    createDemoPhoto('Anita Patel', [100, 200, 150]),
    createDemoPhoto('Vikash Singh', [200, 150, 100]),
    createDemoPhoto('Sunita Devi', [200, 100, 150]),
];

echo "<h2>✅ Demo Photos Created:</h2>";

foreach ($demo_photos as $photo) {
    echo "<div style='display:inline-block; text-align:center; margin:10px;'>";
    echo "<img src='{$photo['thumb_url']}' width='150' height='150'><br>";
    echo "<small>{$photo['filename']}</small>";
    echo "</div>";
}

echo "<h2>📋 Photo Files for Testing:</h2>";
echo "<ul>";
foreach ($demo_photos as $photo) {
    echo "<li><strong>{$photo['filename']}</strong> - {$photo['url']}</li>";
}
echo "</ul>";

echo "<p><a href='admin/students_add.php'>Go to Add Student Form</a></p>";
?>
