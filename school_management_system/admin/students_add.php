<?php
/**
 * Add/Edit Student - Admin Panel
 * Modern student registration form with enhanced features
 */

require_once '../includes/simple_db.php';
// Simplified photo handler - no external dependencies
check_admin();

// Simple photo upload function
function processSimplePhoto($file) {
    $upload_dir = __DIR__ . '/../uploads/students/';
    $thumb_dir = $upload_dir . 'thumbnails/';
    
    // Ensure directories exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (!file_exists($thumb_dir)) {
        mkdir($thumb_dir, 0755, true);
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error occurred'];
    }
    
    if ($file['size'] > 5242880) { // 5MB
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'student_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Create thumbnail copy (GD not available)
        $thumb_path = $thumb_dir . $filename;
        if (copy($filepath, $thumb_path)) {
            error_log('Thumbnail created as copy of original image');
        } else {
            error_log('Failed to create thumbnail copy');
        }
        
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
}

$message = '';
$errors = [];
$edit_mode = false;
$student = [];

// Check if editing existing student
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_mode = true;
    $student_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $errors[] = "Student not found or inactive";
        $edit_mode = false;
    }
    $stmt->close();
}

// Debug: Log all POST data for debugging
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));
    
    // Get and validate form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $parent_mobile = trim($_POST['parent_mobile'] ?? '');
    $mobile_no = trim($_POST['mobile_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $class_id = trim($_POST['class_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $aadhar_no = trim($_POST['aadhar_no'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    
    // Photo handling
    $photo_filename = null;
    $photo_upload_result = null;
    
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
    
    // Mobile validation
    if (!empty($parent_mobile) && !preg_match('/^[0-9]{10}$/', $parent_mobile)) {
        $errors[] = "Parent mobile must be 10 digits";
    }
    
    if (!empty($mobile_no) && !preg_match('/^[0-9]{10}$/', $mobile_no)) {
        $errors[] = "Student mobile must be 10 digits";
    }
    
    // Email validation
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Pincode validation
    if (!empty($pincode) && !preg_match('/^[0-9]{6}$/', $pincode)) {
        $errors[] = "Pincode must be 6 digits";
    }
    
    // Aadhar validation
    if (!empty($aadhar_no) && !preg_match('/^[0-9]{12}$/', $aadhar_no)) {
        $errors[] = "Aadhar number must be 12 digits";
    }
    
    // Handle photo upload if provided (simplified)
    if (!empty($_FILES['photo']['tmp_name'])) {
        error_log('Processing photo upload...');
        $photo_upload_result = processSimplePhoto($_FILES['photo']);
        
        if (!$photo_upload_result['success']) {
            $errors[] = 'Photo upload error: ' . $photo_upload_result['error'];
            error_log('Photo upload failed: ' . $photo_upload_result['error']);
        } else {
            $photo_filename = $photo_upload_result['filename'];
            error_log('Photo uploaded successfully: ' . $photo_filename);
        }
    } else {
        // Photo is optional for new students - set to null if not provided
        $photo_filename = null;
        error_log('No photo provided, proceeding without photo');
    }
    
    // If no errors, insert/update student
    if (empty($errors)) {
        try {
            if ($edit_mode && isset($_POST['student_id'])) {
                // Update existing student
                $student_id = intval($_POST['student_id']);
                
                // Calculate age from date of birth
                $birth_date = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($birth_date)->y;
                
                // Handle photo update for existing student
                $photo_update_sql = '';
                $photo_params = [];
                
                if ($photo_filename) {
                    // Delete old photo if exists
                    if (!empty($student['photo'])) {
                        $old_file = __DIR__ . '/../uploads/students/' . $student['photo'];
                        $old_thumb = __DIR__ . '/../uploads/students/thumbnails/' . $student['photo'];
                        if (file_exists($old_file)) unlink($old_file);
                        if (file_exists($old_thumb)) unlink($old_thumb);
                    }
                    $photo_update_sql = ', photo = ?';
                    $photo_params[] = $photo_filename;
                }
                
                $stmt = $conn->prepare("
                    UPDATE students SET 
                        first_name = ?, last_name = ?, father_name = ?, mother_name = ?,
                        date_of_birth = ?, gender = ?, parent_mobile = ?, mobile_no = ?,
                        address = ?, village = ?, pincode = ?, class_id = ?, email = ?,
                        blood_group = ?, category = ?, religion = ?, aadhar_no = ?, academic_year = ?" . $photo_update_sql . "
                    WHERE id = ?
                ");
                
                // Prepare parameters for binding (removed age)
                $bind_params = [
                    $first_name, $last_name, $father_name, $mother_name,
                    $date_of_birth, $gender, $parent_mobile, $mobile_no,
                    $address, $village, $pincode, $class_id, $email,
                    $blood_group, $category, $religion, $aadhar_no, $academic_year
                ];
                
                $bind_types = "sssssssssssisssss";
                
                if ($photo_filename) {
                    $bind_params = array_merge($bind_params, $photo_params);
                    $bind_types .= "s";
                }
                
                $bind_params[] = $student_id;
                $bind_types .= "i";
                
                $stmt->bind_param($bind_types, ...$bind_params);
                
                if ($stmt->execute()) {
                    $message = "Student updated successfully!";
                } else {
                    $errors[] = "Error updating student: " . $stmt->error;
                }
                
            } else {
                // Add new student
                // Generate admission number
                $result = $conn->query("SELECT COUNT(*) as count FROM students");
                $count = $result->fetch_assoc()['count'];
                $admission_no = 'ADM' . date('Y') . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                
                // Calculate age from date of birth
                $birth_date = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($birth_date)->y;
                
                // Prepare SQL statement with photo (removed age and is_active columns)
                $stmt = $conn->prepare("
                    INSERT INTO students (
                        admission_no, first_name, last_name, father_name, mother_name, 
                        date_of_birth, gender, parent_mobile, mobile_no, address, village, pincode,
                        class_id, academic_year, admission_date, email, blood_group, category, religion,
                        aadhar_no, photo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->bind_param(
                    "ssssssssssssisssssss",
                    $admission_no, $first_name, $last_name, $father_name, $mother_name,
                    $date_of_birth, $gender, $parent_mobile, $mobile_no, $address, $village, $pincode,
                    $class_id, $academic_year, date('Y-m-d'), $email, $blood_group, $category, $religion,
                    $aadhar_no, $photo_filename
                );
                
                if ($stmt->execute()) {
                    error_log("Student inserted successfully with ID: " . $conn->insert_id);
                    // Update photo filename with actual student ID
                    // Photo is already saved with correct filename, no need to rename
                    
                    $message = "Student added successfully! Admission Number: " . $admission_no;
                    // Clear form data for new entry
                    $_POST = [];
                } else {
                    $error_msg = "Error adding student: " . $stmt->error;
                    error_log($error_msg);
                    $errors[] = $error_msg;
                    // Delete uploaded photo if database insert failed
                    if ($photo_filename) {
                        $file_path = __DIR__ . '/../uploads/students/' . $photo_filename;
                        $thumb_path = __DIR__ . '/../uploads/students/thumbnails/' . $photo_filename;
                        if (file_exists($file_path)) unlink($file_path);
                        if (file_exists($thumb_path)) unlink($thumb_path);
                    }
                }
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $error_msg = "Database error: " . $e->getMessage();
            error_log($error_msg);
            $errors[] = $error_msg;
        }
    }
}

// Get classes for dropdown - remove is_active check if column doesn't exist
$classes_result = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name");
$classes = [];
if ($classes_result) {
    while ($row = $classes_result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Helper function to get field value
function getFieldValue($field_name, $student = [], $post_data = []) {
    if (!empty($post_data[$field_name])) {
        return htmlspecialchars($post_data[$field_name]);
    }
    if (!empty($student[$field_name])) {
        return htmlspecialchars($student[$field_name]);
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Student' : 'Add Student'; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="../assets/js/sidebar.js" defer></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-wrapper fade-in">
                <div class="page-header d-flex justify-between align-center">
                    <div>
                        <h1 class="page-title"><?php echo $edit_mode ? '✏️ Edit Student' : '➕ Add New Student'; ?></h1>
                        <p class="page-subtitle"><?php echo $edit_mode ? 'Update student information' : 'Add a new student to the system'; ?></p>
                    </div>
                    <div class="d-flex gap-1 align-center">
                        <a href="students.php" class="btn btn-secondary">← Back to List</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Student Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $edit_mode ? 'Edit Student Details' : 'Student Registration Form'; ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-container" enctype="multipart/form-data">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <!-- Basic Information -->
                                <div class="form-group">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-input" required
                                           value="<?php echo getFieldValue('first_name', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-input" required
                                           value="<?php echo getFieldValue('last_name', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Father's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" class="form-input" required
                                           value="<?php echo getFieldValue('father_name', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Mother's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="mother_name" class="form-input" required
                                           value="<?php echo getFieldValue('mother_name', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-input" required
                                           value="<?php echo getFieldValue('date_of_birth', $student, $_POST); ?>"
                                           max="<?php echo date('Y-m-d', strtotime('-3 years')); ?>"
                                           min="<?php echo date('Y-m-d', strtotime('-25 years')); ?>"
                                           onchange="calculateAge()">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select" required>
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo (getFieldValue('gender', $student, $_POST) == 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo (getFieldValue('gender', $student, $_POST) == 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo (getFieldValue('gender', $student, $_POST) == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <!-- Contact Information -->
                                <div class="form-group">
                                    <label class="form-label">Parent Mobile <span class="text-danger">*</span></label>
                                    <input type="tel" name="parent_mobile" class="form-input" required 
                                           pattern="[0-9]{10}" title="Enter 10-digit mobile number"
                                           value="<?php echo getFieldValue('parent_mobile', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Student Mobile</label>
                                    <input type="tel" name="mobile_no" class="form-input" 
                                           pattern="[0-9]{10}" title="Enter 10-digit mobile number"
                                           value="<?php echo getFieldValue('mobile_no', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-input"
                                           value="<?php echo getFieldValue('email', $student, $_POST); ?>">
                                </div>
                                
                                <!-- Academic Information -->
                                <div class="form-group">
                                    <label class="form-label">Class <span class="text-danger">*</span></label>
                                    <select name="class_id" class="form-select" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" 
                                                    <?php echo (getFieldValue('class_id', $student, $_POST) == $class['id']) ? 'selected' : ''; ?>>
                                                <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select name="academic_year" class="form-select" required>
                                        <option value="">Select Academic Year</option>
                                        <?php 
                                        // Generate academic year options
                                        $current_year = date('Y');
                                        $current_academic_year = $current_year . '-' . ($current_year + 1);
                                        $selected_year = getFieldValue('academic_year', $student, $_POST) ?: $current_academic_year;
                                        
                                        for ($i = -2; $i <= 2; $i++) {
                                            $year = $current_year + $i;
                                            $academic_year = $year . '-' . ($year + 1);
                                            $selected = ($academic_year === $selected_year) ? 'selected' : '';
                                            echo '<option value="' . $academic_year . '" ' . $selected . '>' . $academic_year . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- Additional Information -->
                                <div class="form-group">
                                    <label class="form-label">Blood Group</label>
                                    <select name="blood_group" class="form-select">
                                        <option value="">Select Blood Group</option>
                                        <?php 
                                        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                        foreach ($blood_groups as $bg): ?>
                                            <option value="<?php echo $bg; ?>" <?php echo (getFieldValue('blood_group', $student, $_POST) == $bg) ? 'selected' : ''; ?>>
                                                <?php echo $bg; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select">
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories = ['General', 'OBC', 'SC', 'ST', 'EWS'];
                                        foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>" <?php echo (getFieldValue('category', $student, $_POST) == $cat) ? 'selected' : ''; ?>>
                                                <?php echo $cat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Religion</label>
                                    <input type="text" name="religion" class="form-input"
                                           value="<?php echo getFieldValue('religion', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Aadhar Number</label>
                                    <input type="text" name="aadhar_no" class="form-input" maxlength="12"
                                           pattern="[0-9]{12}" title="Enter 12-digit Aadhar number"
                                           value="<?php echo getFieldValue('aadhar_no', $student, $_POST); ?>">
                                </div>
                                
                                <!-- Photo Upload Section -->
                                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Student Photo <small class="text-muted">(Optional)</small></label>
                                    <div class="photo-upload-container">
                                        <?php if ($edit_mode && !empty($student['photo'])): ?>
                                            <div class="current-photo">
                                                <img src="../uploads/students/thumbnails/<?php echo htmlspecialchars($student['photo']); ?>" 
                                                     alt="Current Photo" class="current-student-photo" 
                                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;"
                                                     onerror="this.src='../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>'">
                                                <p class="photo-info">Current photo - Upload a new one to replace</p>
                                            </div>
                                        <?php endif; ?>
                                        
                        <input type="file" name="photo" class="form-input" accept="image/*" 
                               onchange="previewPhoto(this)">
                                        
                                        <div class="photo-preview" id="photoPreview" style="display: none; margin-top: 10px;">
                                            <img id="previewImage" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                                            <p class="photo-info">Photo preview</p>
                                        </div>
                                        
                                        <div class="form-help">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i> 
                                                Upload a clear photo (JPG, PNG, GIF). Maximum size: 5MB. Image will be resized automatically.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Address Information -->
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-textarea" required><?php echo getFieldValue('address', $student, $_POST); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Village/City <span class="text-danger">*</span></label>
                                    <input type="text" name="village" class="form-input" required
                                           value="<?php echo getFieldValue('village', $student, $_POST); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode" class="form-input" maxlength="6" 
                                           pattern="[0-9]{6}" title="Enter 6-digit pincode"
                                           value="<?php echo getFieldValue('pincode', $student, $_POST); ?>">
                                </div>
                            </div>
                            
                            <div class="mt-4 d-flex gap-2">
                                <button type="button" class="btn btn-info" onclick="fillDemoData()">
                                    🧪 Fill Demo Data
                                </button>
                                <button type="submit" name="add_student" class="btn btn-primary" id="submitBtn">
                                    <?php echo $edit_mode ? '💾 Update Student' : '➕ Add Student'; ?>
                                </button>
                                <a href="students.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/modern-ui.js"></script>

    <script>
        // Function to calculate age from date of birth
        function calculateAge() {
            const dobInput = document.querySelector('input[name="date_of_birth"]');
            
            if (dobInput && dobInput.value) {
                const dob = new Date(dobInput.value);
                const today = new Date();
                
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                // Adjust age if birthday hasn't occurred this year
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                console.log('Calculated age:', age);
            }
        }
        
        // Function to fill demo data for testing
        function fillDemoData() {
            document.querySelector('input[name="first_name"]').value = 'Priya';
            document.querySelector('input[name="last_name"]').value = 'Sharma';
            document.querySelector('input[name="father_name"]').value = 'Rajesh Sharma';
            document.querySelector('input[name="mother_name"]').value = 'Meera Sharma';
            document.querySelector('input[name="date_of_birth"]').value = '2010-08-15';
            document.querySelector('select[name="gender"]').value = 'female';
            document.querySelector('select[name="blood_group"]').value = 'O+';
            document.querySelector('select[name="category"]').value = 'General';
            document.querySelector('input[name="religion"]').value = 'Hindu';
            document.querySelector('input[name="mobile_no"]').value = '9876543210';
            document.querySelector('input[name="parent_mobile"]').value = '9123456789';
            document.querySelector('input[name="email"]').value = 'priya.sharma@email.com';
            document.querySelector('textarea[name="address"]').value = 'House No. 456, Gandhi Nagar, Near Park';
            document.querySelector('input[name="village"]').value = 'Indore';
            document.querySelector('input[name="pincode"]').value = '452001';
            document.querySelector('input[name="aadhar_no"]').value = '123456789012';
            
            // Set class to first available option
            const classSelect = document.querySelector('select[name="class_id"]');
            if (classSelect.options.length > 1) {
                classSelect.selectedIndex = 1; // Select first actual class
            }
            
            // Calculate age after setting date of birth
            calculateAge();
            
            alert('Demo data filled! Now you can submit the form.');
        }
        
        // Photo preview function
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const previewImg = document.getElementById('previewImage');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file type
                if (!file.type.match('image.*')) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5242880) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Hide current photo if exists
                    const currentPhoto = document.querySelector('.current-photo');
                    if (currentPhoto) {
                        currentPhoto.style.opacity = '0.5';
                    }
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                // Restore current photo visibility
                const currentPhoto = document.querySelector('.current-photo');
                if (currentPhoto) {
                    currentPhoto.style.opacity = '1';
                }
            }
        }
        
        // Form submission handler
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    if (form.checkValidity()) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '⏳ Submitting...';
                        submitBtn.disabled = true;
                        
                        // Re-enable after 3 seconds in case of issues
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 3000);
                        
                        return true;
                    }
                });
            }
        });
    </script>
</body>
</html>
