<?php
/**
 * Simple Student Add Test - Minimal version to isolate the issue
 */

require_once '../includes/simple_db.php';
check_admin();

$message = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    echo "<div style='background:#f0f0f0; padding:15px; margin:10px; border:1px solid #ccc;'>";
    echo "<h3>DEBUG: Form Submitted</h3>";
    echo "<pre>POST Data: " . print_r($_POST, true) . "</pre>";
    
    // Get and validate form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $parent_mobile = trim($_POST['parent_mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $class_id = trim($_POST['class_id'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    
    // Basic validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($father_name)) $errors[] = "Father's name is required";
    if (empty($mother_name)) $errors[] = "Mother's name is required";
    if (empty($date_of_birth)) $errors[] = "Date of birth is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($parent_mobile)) $errors[] = "Parent mobile is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($village)) $errors[] = "Village/City is required";
    if (empty($class_id)) $errors[] = "Class is required";
    if (empty($academic_year)) $errors[] = "Academic year is required";
    
    echo "<p><strong>Validation Errors:</strong> " . count($errors) . "</p>";
    if (!empty($errors)) {
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    
    // If no errors, insert student
    if (empty($errors)) {
        try {
            echo "<p><strong>Attempting database insert...</strong></p>";
            
            // Generate admission number
            $result = $conn->query("SELECT COUNT(*) as count FROM students");
            $count = $result->fetch_assoc()['count'];
            $admission_no = 'ADM' . date('Y') . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            echo "<p>Generated Admission No: $admission_no</p>";
            
            // Minimal insert statement
            $stmt = $conn->prepare("
                INSERT INTO students (
                    admission_no, first_name, last_name, father_name, mother_name, 
                    date_of_birth, gender, parent_mobile, address, village,
                    class_id, academic_year, admission_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                echo "<p>❌ Prepare failed: " . $conn->error . "</p>";
            } else {
                echo "<p>✅ SQL statement prepared successfully</p>";
                
                $admission_date = date('Y-m-d');
                $stmt->bind_param(
                    "sssssssssssss",
                    $admission_no, $first_name, $last_name, $father_name, $mother_name,
                    $date_of_birth, $gender, $parent_mobile, $address, $village,
                    $class_id, $academic_year, $admission_date
                );
                
                echo "<p>Parameters bound successfully</p>";
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    $message = "✅ Student added successfully! ID: $new_id, Admission Number: $admission_no";
                    echo "<p>$message</p>";
                    
                    // Clear form data for new entry
                    $_POST = [];
                } else {
                    $error_msg = "❌ Execute failed: " . $stmt->error;
                    echo "<p>$error_msg</p>";
                    $errors[] = $error_msg;
                }
                $stmt->close();
            }
            
        } catch (Exception $e) {
            $error_msg = "❌ Exception: " . $e->getMessage();
            echo "<p>$error_msg</p>";
            $errors[] = $error_msg;
        }
    }
    echo "</div>";
}

// Get classes for dropdown
$classes_result = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name");
$classes = [];
if ($classes_result) {
    while ($row = $classes_result->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Student Test Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-input, .form-select, .form-textarea { 
            width: 100%; max-width: 300px; padding: 8px; border: 1px solid #ccc; 
        }
        .form-textarea { height: 80px; }
        .btn { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .btn-primary { background: #007cba; color: white; border: none; }
        .btn-info { background: #17a2b8; color: white; border: none; }
        .alert { padding: 15px; margin: 15px 0; border: 1px solid; }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .debug { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007cba; }
    </style>
</head>
<body>
    <h1>🧪 Simple Student Test Form</h1>
    <p>This is a simplified version to test the student insertion without photo handler complexity.</p>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="debug">
        <h3>📊 Debug Information</h3>
        <p><strong>Classes available:</strong> <?php echo count($classes); ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>MySQL Version:</strong> <?php echo $conn->server_info; ?></p>
    </div>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Father's Name *</label>
            <input type="text" name="father_name" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Mother's Name *</label>
            <input type="text" name="mother_name" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['mother_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Date of Birth *</label>
            <input type="date" name="date_of_birth" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Gender *</label>
            <select name="gender" class="form-select" required>
                <option value="">Select Gender</option>
                <option value="male" <?php echo ($_POST['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo ($_POST['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Parent Mobile *</label>
            <input type="tel" name="parent_mobile" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['parent_mobile'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Address *</label>
            <textarea name="address" class="form-textarea" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Village/City *</label>
            <input type="text" name="village" class="form-input" required
                   value="<?php echo htmlspecialchars($_POST['village'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Class *</label>
            <select name="class_id" class="form-select" required>
                <option value="">Select Class</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" 
                            <?php echo ($_POST['class_id'] ?? '') == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Academic Year *</label>
            <select name="academic_year" class="form-select" required>
                <option value="">Select Academic Year</option>
                <option value="2024-2025" <?php echo ($_POST['academic_year'] ?? '') == '2024-2025' ? 'selected' : ''; ?>>2024-2025</option>
                <option value="2025-2026" <?php echo ($_POST['academic_year'] ?? '') == '2025-2026' ? 'selected' : ''; ?>>2025-2026</option>
            </select>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="button" class="btn btn-info" onclick="fillDemoData()">
                🧪 Fill Demo Data
            </button>
            <button type="submit" name="add_student" class="btn btn-primary">
                ➕ Add Student
            </button>
        </div>
    </form>

    <script>
        function fillDemoData() {
            document.querySelector('input[name="first_name"]').value = 'John';
            document.querySelector('input[name="last_name"]').value = 'Doe';
            document.querySelector('input[name="father_name"]').value = 'Robert Doe';
            document.querySelector('input[name="mother_name"]').value = 'Jane Doe';
            document.querySelector('input[name="date_of_birth"]').value = '2010-05-15';
            document.querySelector('select[name="gender"]').value = 'male';
            document.querySelector('input[name="parent_mobile"]').value = '9876543210';
            document.querySelector('textarea[name="address"]').value = '123 Main Street, Test Area';
            document.querySelector('input[name="village"]').value = 'Test City';
            
            // Set class to first available option
            const classSelect = document.querySelector('select[name="class_id"]');
            if (classSelect.options.length > 1) {
                classSelect.selectedIndex = 1;
            }
            
            document.querySelector('select[name="academic_year"]').value = '2024-2025';
            
            alert('Demo data filled! You can now submit the form.');
        }
    </script>

    <hr>
    <p><a href="../debug_student_form.php">← Back to Debug Page</a> | 
       <a href="students_add.php">Go to Full Form</a></p>
</body>
</html>
