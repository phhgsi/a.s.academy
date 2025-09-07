<?php
require_once '../config/database.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Get teacher information
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email 
    FROM teachers t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.user_id = ? AND t.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    $error = 'Teacher profile not found. Please contact administrator.';
} else {
    // Get assigned classes and subjects
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.class_name, c.section, s.subject_name
        FROM class_subjects cs
        LEFT JOIN classes c ON cs.class_id = c.id
        LEFT JOIN subjects s ON cs.subject_id = s.id
        WHERE cs.teacher_id = ?
        ORDER BY c.class_name, c.section, s.subject_name
    ");
    $stmt->execute([$teacher['id']]);
    $assignments = $stmt->fetchAll();
    
    // Group by class
    $class_assignments = [];
    foreach ($assignments as $assignment) {
        $class_key = $assignment['class_name'] . ' ' . $assignment['section'];
        if (!isset($class_assignments[$class_key])) {
            $class_assignments[$class_key] = [];
        }
        $class_assignments[$class_key][] = $assignment['subject_name'];
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $employee_id = $_POST['employee_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $qualification = $_POST['qualification'];
    $experience_years = $_POST['experience_years'];
    $mobile_no = $_POST['mobile_no'];
    $emergency_contact = $_POST['emergency_contact'];
    $address = $_POST['address'];
    $joining_date = $_POST['joining_date'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE teachers 
            SET employee_id = ?, first_name = ?, last_name = ?, qualification = ?, 
                experience_years = ?, mobile_no = ?, emergency_contact = ?, address = ?, 
                joining_date = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$employee_id, $first_name, $last_name, $qualification, 
                       $experience_years, $mobile_no, $emergency_contact, $address, 
                       $joining_date, $_SESSION['user_id']]);
        
        $success = 'Profile updated successfully!';
        
        // Refresh teacher data
        $stmt = $pdo->prepare("
            SELECT t.*, u.username, u.email 
            FROM teachers t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.user_id = ? AND t.is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Teacher Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">👨‍🏫 My Profile</h1>
                    <p class="page-subtitle">View and update your profile information</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($teacher): ?>
                    <!-- Teacher Profile Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                            <div class="card-actions">
                                <button type="button" onclick="toggleEdit()" class="btn btn-primary" id="editBtn">
                                    ✏️ Edit Profile
                                </button>
                                <button class="btn btn-outline" onclick="printSection('profileDetails')">🖨️ Print Profile</button>
                            </div>
                        </div>
                        
                        <div id="profileDetails">
                            <!-- View Mode -->
                            <div id="viewMode">
                                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
                                    <div class="text-center">
                                        <?php if ($teacher['photo']): ?>
                                            <img src="../uploads/photos/<?php echo $teacher['photo']; ?>" 
                                                 alt="Teacher Photo" class="photo-preview" style="width: 180px; height: 180px;">
                                        <?php else: ?>
                                            <div style="width: 180px; height: 180px; background: var(--primary-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 600;">
                                                <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="margin-top: 1rem; padding: 1rem; background: var(--light-color); border-radius: var(--border-radius);">
                                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($teacher['employee_id']); ?>
                                            </div>
                                            <small>Employee ID</small>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </h2>
                                        
                                        <!-- Basic Information -->
                                        <div class="mb-3">
                                            <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Basic Information</h4>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                                <div><strong>Name:</strong> <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></div>
                                                <div><strong>Employee ID:</strong> <?php echo htmlspecialchars($teacher['employee_id']); ?></div>
                                                <div><strong>Qualification:</strong> <?php echo htmlspecialchars($teacher['qualification'] ?: 'Not specified'); ?></div>
                                                <div><strong>Experience:</strong> <?php echo htmlspecialchars($teacher['experience_years'] ?: '0'); ?> years</div>
                                                <div><strong>Joining Date:</strong> <?php echo $teacher['joining_date'] ? date('d/m/Y', strtotime($teacher['joining_date'])) : 'Not specified'; ?></div>
                                                <div><strong>Department:</strong> <?php echo htmlspecialchars($teacher['department'] ?: 'Not assigned'); ?></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Contact Information -->
                                        <div class="mb-3">
                                            <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Contact Information</h4>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                                <div><strong>Mobile:</strong> <?php echo htmlspecialchars($teacher['mobile_no'] ?: 'Not provided'); ?></div>
                                                <div><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($teacher['emergency_contact'] ?: 'Not provided'); ?></div>
                                                <div><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></div>
                                                <div><strong>Username:</strong> <?php echo htmlspecialchars($teacher['username']); ?></div>
                                            </div>
                                            
                                            <?php if ($teacher['address']): ?>
                                                <div style="margin-top: 1rem;">
                                                    <strong>Address:</strong><br>
                                                    <div style="background: white; padding: 1rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); margin-top: 0.5rem;">
                                                        <?php echo nl2br(htmlspecialchars($teacher['address'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div id="editMode" style="display: none;">
                                <form method="POST">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                        <div>
                                            <label>Employee ID:</label>
                                            <input type="text" name="employee_id" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['employee_id']); ?>" required>
                                        </div>
                                        <div>
                                            <label>First Name:</label>
                                            <input type="text" name="first_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                                        </div>
                                        <div>
                                            <label>Last Name:</label>
                                            <input type="text" name="last_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                                        </div>
                                        <div>
                                            <label>Qualification:</label>
                                            <input type="text" name="qualification" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['qualification']); ?>">
                                        </div>
                                        <div>
                                            <label>Experience (Years):</label>
                                            <input type="number" name="experience_years" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['experience_years']); ?>" min="0">
                                        </div>
                                        <div>
                                            <label>Joining Date:</label>
                                            <input type="date" name="joining_date" class="form-control" 
                                                   value="<?php echo $teacher['joining_date']; ?>">
                                        </div>
                                        <div>
                                            <label>Mobile Number:</label>
                                            <input type="tel" name="mobile_no" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['mobile_no']); ?>">
                                        </div>
                                        <div>
                                            <label>Emergency Contact:</label>
                                            <input type="tel" name="emergency_contact" class="form-control" 
                                                   value="<?php echo htmlspecialchars($teacher['emergency_contact']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 1rem;">
                                        <label>Address:</label>
                                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($teacher['address']); ?></textarea>
                                    </div>
                                    
                                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                        <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
                                        <button type="button" onclick="cancelEdit()" class="btn btn-secondary">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Assignments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📚 My Teaching Assignments</h3>
                        </div>
                        
                        <?php if (empty($class_assignments)): ?>
                            <div class="alert alert-info">
                                No class or subject assignments found. Please contact the administrator for your teaching assignments.
                            </div>
                        <?php else: ?>
                            <div style="display: grid; gap: 1.5rem;">
                                <?php foreach ($class_assignments as $class => $subjects): ?>
                                    <div style="background: var(--light-color); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                                        <h4 style="color: var(--primary-color); margin-bottom: 1rem;">
                                            📖 Class <?php echo htmlspecialchars($class); ?>
                                        </h4>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            <?php foreach ($subjects as $subject): ?>
                                                <span class="subject-badge"><?php echo htmlspecialchars($subject); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚡ Quick Actions</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="academics.php" class="btn btn-primary">
                                📊 Manage Academic Records
                            </a>
                            <a href="attendance.php" class="btn btn-warning">
                                📅 Mark Attendance
                            </a>
                            <a href="students.php" class="btn btn-info">
                                👥 View My Students
                            </a>
                            <a href="reports.php" class="btn btn-secondary">
                                📋 Generate Reports
                            </a>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">ℹ️ Account Information</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div><strong>Account Created:</strong> <?php echo date('d/m/Y', strtotime($teacher['created_at'])); ?></div>
                            <div><strong>Last Updated:</strong> <?php echo date('d/m/Y H:i', strtotime($teacher['updated_at'])); ?></div>
                            <div><strong>Account Status:</strong> <span class="status-badge status-active">Active</span></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <style>
    .subject-badge {
        background: var(--primary-color);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
        display: inline-block;
    }

    .status-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .photo-preview {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: var(--border-radius);
        border: 3px solid var(--border-color);
    }

    @media (max-width: 768px) {
        #profileDetails > div > div:first-child {
            grid-column: 1 / -1;
        }
        
        #profileDetails > div {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }
    }
    </style>

    <script>
    function toggleEdit() {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');
        const editBtn = document.getElementById('editBtn');
        
        if (viewMode.style.display === 'none') {
            // Switch to view mode
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            editBtn.innerHTML = '✏️ Edit Profile';
        } else {
            // Switch to edit mode
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
            editBtn.innerHTML = '👁️ View Profile';
        }
    }

    function cancelEdit() {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');
        const editBtn = document.getElementById('editBtn');
        
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        editBtn.innerHTML = '✏️ Edit Profile';
    }
    </script>
</body>
</html>
