<?php
/**
 * Debug Student Form Submission
 * Test the exact issue and create demo photo
 */

require_once 'includes/simple_db.php';

// Function to create a demo photo
function createDemoPhoto() {
    $upload_dir = __DIR__ . '/uploads/students/';
    $thumb_dir = $upload_dir . 'thumbnails/';
    
    // Ensure directories exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (!file_exists($thumb_dir)) {
        mkdir($thumb_dir, 0755, true);
    }
    
    // Create a simple demo image
    $width = 200;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $blue = imagecolorallocate($image, 50, 100, 200);
    $white = imagecolorallocate($image, 255, 255, 255);
    
    // Fill background
    imagefill($image, 0, 0, $blue);
    
    // Add text
    $text = "DEMO PHOTO";
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y, $text, $white);
    
    // Save demo photo
    $filename = 'demo_student_photo.jpg';
    $filepath = $upload_dir . $filename;
    $thumb_filepath = $thumb_dir . $filename;
    
    imagejpeg($image, $filepath, 90);
    
    // Create thumbnail
    $thumb = imagescale($image, 150, 150);
    imagejpeg($thumb, $thumb_filepath, 90);
    
    // Clean up
    imagedestroy($image);
    imagedestroy($thumb);
    
    return $filename;
}

// Check database connection
echo "<h1>Student Form Debug</h1>";
echo "<style>body{font-family: Arial; margin: 40px;} pre{background:#f5f5f5;padding:10px;}</style>";

try {
    echo "<h2>✅ Database Connection Test</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM students");
    $count = $result->fetch_assoc()['count'];
    echo "<p>Current students count: {$count}</p>";
    
    // Check students table structure
    echo "<h2>📋 Students Table Structure</h2>";
    $result = $conn->query("DESCRIBE students");
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-20s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
    echo "</pre>";
    
    // Check classes table
    echo "<h2>📚 Classes Available</h2>";
    $result = $conn->query("SELECT id, class_name, section FROM classes LIMIT 5");
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Class: {$row['class_name']} {$row['section']}\n";
    }
    echo "</pre>";
    
    // Create demo photo
    echo "<h2>📷 Demo Photo Creation</h2>";
    $demo_photo = createDemoPhoto();
    echo "<p>✅ Demo photo created: {$demo_photo}</p>";
    echo "<img src='uploads/students/{$demo_photo}' style='border:1px solid #ccc;'>";
    
    // Test simple insert query
    echo "<h2>🧪 Test Student Insert Query</h2>";
    
    $test_data = [
        'admission_no' => 'TEST' . time(),
        'first_name' => 'Test',
        'last_name' => 'Student',
        'father_name' => 'Test Father',
        'mother_name' => 'Test Mother',
        'date_of_birth' => '2010-01-01',
        'gender' => 'male',
        'parent_mobile' => '9999999999',
        'mobile_no' => '',
        'address' => 'Test Address',
        'village' => 'Test City',
        'pincode' => '123456',
        'class_id' => 1,
        'academic_year' => '2024-2025',
        'admission_date' => date('Y-m-d'),
        'email' => 'test@example.com',
        'blood_group' => 'O+',
        'category' => 'General',
        'religion' => 'Hindu',
        'aadhar_no' => '123456789012',
        'photo' => $demo_photo
    ];
    
    $sql = "INSERT INTO students (
        admission_no, first_name, last_name, father_name, mother_name, 
        date_of_birth, gender, parent_mobile, mobile_no, address, village, pincode,
        class_id, academic_year, admission_date, email, blood_group, category, religion,
        aadhar_no, photo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<p>❌ Prepare failed: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param(
            "ssssssssssssisssssss",
            $test_data['admission_no'], $test_data['first_name'], $test_data['last_name'], 
            $test_data['father_name'], $test_data['mother_name'], $test_data['date_of_birth'], 
            $test_data['gender'], $test_data['parent_mobile'], $test_data['mobile_no'], 
            $test_data['address'], $test_data['village'], $test_data['pincode'],
            $test_data['class_id'], $test_data['academic_year'], $test_data['admission_date'], 
            $test_data['email'], $test_data['blood_group'], $test_data['category'], 
            $test_data['religion'], $test_data['aadhar_no'], $test_data['photo']
        );
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            echo "<p>✅ Test student inserted successfully! ID: {$new_id}</p>";
            
            // Clean up test data
            $conn->query("DELETE FROM students WHERE id = {$new_id}");
            echo "<p>🧹 Test data cleaned up</p>";
        } else {
            echo "<p>❌ Insert failed: " . $stmt->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Show recent PHP errors
echo "<h2>🐛 Recent PHP Errors</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $errors = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $errors), -20);
    echo "<pre style='max-height:300px;overflow-y:auto;'>";
    echo implode("\n", $recent_errors);
    echo "</pre>";
} else {
    echo "<p>No error log file found or not accessible.</p>";
}

echo "<hr><p>Debug completed at: " . date('Y-m-d H:i:s') . "</p>";
?>

<h2>🔗 Quick Links</h2>
<ul>
    <li><a href="admin/students_add.php">Go to Add Student Form</a></li>
    <li><a href="test_database.php">Run Database Tests</a></li>
    <li><a href="admin/enhanced_dashboard.php">View Dashboard</a></li>
</ul>

<script>
// Auto-fill demo form function for testing
function fillDemoForm() {
    // This will be used in the actual form
    console.log('Demo form filling script ready');
}
</script>
