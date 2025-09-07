<?php
require_once '../config/database.php';
require_once '../includes/academic_year.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Get student ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: students_list.php');
    exit();
}

$student_id = $_GET['id'];

// Get single student for editing
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND is_active = 1");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['success_message'] = 'Student not found!';
    $_SESSION['success_type'] = 'error';
    header('Location: students_list.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    // Handle photo upload for update
    $new_photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "../uploads/photos/";
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_photo = $student['admission_no'] . '_photo.' . $file_extension;
        $target_file = $target_dir . $new_photo;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $new_photo = null;
        }
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Prepare SQL with or without photo update
        if ($new_photo !== null) {
            $sql = "
                UPDATE students SET 
                    first_name = ?, last_name = ?, father_name = ?, mother_name = ?, 
                    date_of_birth = ?, gender = ?, blood_group = ?, category = ?, 
                    religion = ?, mobile_no = ?, parent_mobile = ?, email = ?, 
                    address = ?, village = ?, pincode = ?, aadhar_no = ?, 
                    samagra_id = ?, pan_no = ?, scholar_no = ?, class_id = ?, 
                    academic_year = ?, admission_date = ?, photo = ?
                WHERE id = ?
            ";
            $params = [
                $_POST['first_name'], $_POST['last_name'], $_POST['father_name'], 
                $_POST['mother_name'], $_POST['date_of_birth'], $_POST['gender'], 
                $_POST['blood_group'], $_POST['category'], $_POST['religion'], 
                $_POST['mobile_no'], $_POST['parent_mobile'], $_POST['email'], 
                $_POST['address'], $_POST['village'], $_POST['pincode'], 
                $_POST['aadhar_no'], $_POST['samagra_id'], $_POST['pan_no'], 
                $_POST['scholar_no'], $_POST['class_id'], $_POST['academic_year'], 
                $_POST['admission_date'], $new_photo, $_POST['student_id']
            ];
        } else {
            $sql = "
                UPDATE students SET 
                    first_name = ?, last_name = ?, father_name = ?, mother_name = ?, 
                    date_of_birth = ?, gender = ?, blood_group = ?, category = ?, 
                    religion = ?, mobile_no = ?, parent_mobile = ?, email = ?, 
                    address = ?, village = ?, pincode = ?, aadhar_no = ?, 
                    samagra_id = ?, pan_no = ?, scholar_no = ?, class_id = ?, 
                    academic_year = ?, admission_date = ?
                WHERE id = ?
            ";
            $params = [
                $_POST['first_name'], $_POST['last_name'], $_POST['father_name'], 
                $_POST['mother_name'], $_POST['date_of_birth'], $_POST['gender'], 
                $_POST['blood_group'], $_POST['category'], $_POST['religion'], 
                $_POST['mobile_no'], $_POST['parent_mobile'], $_POST['email'], 
                $_POST['address'], $_POST['village'], $_POST['pincode'], 
                $_POST['aadhar_no'], $_POST['samagra_id'], $_POST['pan_no'], 
                $_POST['scholar_no'], $_POST['class_id'], $_POST['academic_year'], 
                $_POST['admission_date'], $_POST['student_id']
            ];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Commit transaction
        $pdo->commit();
        
        // Set success message and redirect
        $_SESSION['success_message'] = 'Student updated successfully!';
        $_SESSION['success_type'] = 'success';
        header('Location: students_list.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $message = 'Error updating student: ' . $e->getMessage();
    }
}

// Get classes for dropdown
$stmt = $pdo->prepare("SELECT * FROM classes WHERE is_active = 1 ORDER BY class_name");
$stmt->execute();
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="../assets/css/photo-capture.css">
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
                        <h1 class="page-title">Edit Student</h1>
                        <p class="page-subtitle">Update student information</p>
                    </div>
                    <div class="d-flex gap-1 align-center">
                        <a href="students_list.php" class="btn btn-secondary">← Back to List</a>
                        <a href="students_view.php?id=<?php echo $student['id']; ?>" class="btn btn-outline">👁️ View Details</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>" style="font-size: 1.1rem; padding: 1rem; margin: 1rem 0;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Student Form -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <h3 style="margin-bottom: 2rem; color: var(--primary-color);">
                            ✏️ Edit Student: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                        </h3>
                        
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        
                        <div class="form-grid">
                            <!-- Basic Information -->
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-input" required
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-input" required
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Father's Name *</label>
                                <input type="text" name="father_name" class="form-input" required
                                       value="<?php echo htmlspecialchars($student['father_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Mother's Name *</label>
                                <input type="text" name="mother_name" class="form-input" required
                                       value="<?php echo htmlspecialchars($student['mother_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name="date_of_birth" class="form-input" required
                                       value="<?php echo $student['date_of_birth']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($student['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select Blood Group</option>
                                    <?php 
                                    $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                    foreach ($blood_groups as $bg): ?>
                                        <option value="<?php echo $bg; ?>" <?php echo ($student['blood_group'] == $bg) ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $cat; ?>" <?php echo ($student['category'] == $cat) ? 'selected' : ''; ?>>
                                            <?php echo $cat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Religion</label>
                                <input type="text" name="religion" class="form-input"
                                       value="<?php echo htmlspecialchars($student['religion']); ?>">
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="form-group">
                                <label class="form-label">Student Mobile</label>
                                <input type="tel" name="mobile_no" class="form-input" pattern="[0-9]{10}" title="Enter 10-digit mobile number"
                                       value="<?php echo htmlspecialchars($student['mobile_no']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Parent Mobile *</label>
                                <input type="tel" name="parent_mobile" class="form-input" required pattern="[0-9]{10}" title="Enter 10-digit mobile number"
                                       value="<?php echo htmlspecialchars($student['parent_mobile']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input"
                                       value="<?php echo htmlspecialchars($student['email']); ?>">
                            </div>
                            
                            <!-- Address Information -->
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Address *</label>
                                <textarea name="address" class="form-textarea" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Village/City *</label>
                                <input type="text" name="village" class="form-input" required
                                       value="<?php echo htmlspecialchars($student['village']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-input" maxlength="6" pattern="[0-9]{6}" title="Enter 6-digit pincode"
                                       value="<?php echo htmlspecialchars($student['pincode']); ?>">
                            </div>
                            
                            <!-- Government IDs -->
                            <div class="form-group">
                                <label class="form-label">Aadhar Number</label>
                                <input type="text" name="aadhar_no" class="form-input" maxlength="12"
                                       value="<?php echo htmlspecialchars($student['aadhar_no']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Samagra ID</label>
                                <input type="text" name="samagra_id" class="form-input"
                                       value="<?php echo htmlspecialchars($student['samagra_id']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">PAN Number</label>
                                <input type="text" name="pan_no" class="form-input" maxlength="10"
                                       value="<?php echo htmlspecialchars($student['pan_no']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Scholar Number</label>
                                <input type="text" name="scholar_no" class="form-input"
                                       value="<?php echo htmlspecialchars($student['scholar_no']); ?>">
                            </div>
                            
                            <!-- Academic Information -->
                            <div class="form-group">
                                <label class="form-label">Class *</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo ($student['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Academic Year *</label>
                                <select name="academic_year" class="form-select" required>
                                    <option value="">Select Academic Year</option>
                                    <?php 
                                    $current_year = date('Y');
                                    $selected_year = $student['academic_year'];
                                    
                                    // Generate a few academic years
                                    for ($i = -2; $i <= 2; $i++) {
                                        $year = $current_year + $i;
                                        $academic_year = $year . '-' . ($year + 1);
                                        $selected = ($academic_year === $selected_year) ? 'selected' : '';
                                        echo '<option value="' . $academic_year . '" ' . $selected . '>' . $academic_year . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Admission Date *</label>
                                <input type="date" name="admission_date" class="form-input" required
                                       value="<?php echo $student['admission_date']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Admission Number</label>
                                <input type="text" name="admission_no" class="form-input" readonly
                                       value="<?php echo htmlspecialchars($student['admission_no']); ?>">
                            </div>
                            
                            <!-- Photo Upload with Camera Capture -->
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Student Photo</label>
                                
                                <div class="photo-capture-container">
                                    <div class="photo-controls">
                                        <div class="upload-options">
                                            <input type="file" name="photo" accept="image/*" class="file-upload-input" id="photoFile">
                                            <label for="photoFile" class="btn btn-outline">
                                                <i class="bi bi-upload"></i> Upload Photo
                                            </label>
                                            <button type="button" class="btn btn-primary" onclick="openCameraModal()">
                                                <i class="bi bi-camera"></i> Capture Photo
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="photo-preview-container">
                                        <div class="passport-photo-frame">
                                            <?php if ($student['photo']): ?>
                                                <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                                     alt="Student Photo" id="photoPreview" class="passport-photo">
                                            <?php else: ?>
                                                <div id="photoPlaceholder" class="photo-placeholder">
                                                    <i class="bi bi-person-fill"></i>
                                                    <span>Passport Size Photo</span>
                                                    <small>3.5 x 4.5 cm</small>
                                                </div>
                                                <img src="#" alt="Photo Preview" id="photoPreview" class="passport-photo" style="display: none;">
                                            <?php endif; ?>
                                        </div>
                                        <div class="photo-actions" id="photoActions" style="<?php echo $student['photo'] ? '' : 'display: none;'; ?>">
                                            <button type="button" class="btn btn-sm btn-outline" onclick="editPhoto()">
                                                <i class="bi bi-crop"></i> Crop
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removePhoto()">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden canvas for photo processing -->
                                <canvas id="photoCanvas" style="display: none;"></canvas>
                            </div>
                        </div>
                        
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" name="update_student" class="btn btn-primary" id="submitBtn">
                                ✏️ Update Student
                            </button>
                            <a href="students_list.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/photo-capture.js"></script>

    <script>
        // Photo handling functions
        function initializePhotoCapture() {
            const photoInput = document.getElementById('photoFile');
            if (photoInput) {
                photoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.getElementById('photoPreview');
                            const placeholder = document.getElementById('photoPlaceholder');
                            if (preview) {
                                preview.src = e.target.result;
                                preview.style.display = 'block';
                                if (placeholder) placeholder.style.display = 'none';
                                
                                const actions = document.getElementById('photoActions');
                                if (actions) actions.style.display = 'block';
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }
        
        function removePhoto() {
            const preview = document.getElementById('photoPreview');
            const placeholder = document.getElementById('photoPlaceholder');
            const photoInput = document.getElementById('photoFile');
            const actions = document.getElementById('photoActions');
            
            if (preview) preview.style.display = 'none';
            if (placeholder) placeholder.style.display = 'flex';
            if (photoInput) photoInput.value = '';
            if (actions) actions.style.display = 'none';
        }
        
        function editPhoto() {
            alert('Photo editing feature coming soon!');
        }
        
        function openCameraModal() {
            alert('Camera capture feature requires additional setup. Use the Upload Photo option instead.');
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializePhotoCapture();
            
            // Form submission handler
            const form = document.querySelector('form[method="POST"]');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    if (form.checkValidity()) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '⏳ Updating...';
                        submitBtn.disabled = true;
                        
                        console.log('Form submission started');
                        return true;
                    }
                });
            }
        });
    </script>
</body>
</html>
