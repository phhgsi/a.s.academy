<?php
require_once '../config/database.php';

// Check if user is student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student information
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, u.username, u.email as user_email
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ? AND s.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    $error = 'Student profile not found. Please contact administrator.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">View your personal information and details</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <!-- Student Profile Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                            <button class="btn btn-outline" onclick="printSection('profileDetails')">🖨️ Print Profile</button>
                        </div>
                        
                        <div id="profileDetails">
                            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
                                <div class="text-center">
                                    <?php if ($student['photo']): ?>
                                        <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                             alt="Student Photo" class="photo-preview" style="width: 180px; height: 180px;">
                                    <?php else: ?>
                                        <div style="width: 180px; height: 180px; background: var(--primary-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 600;">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="margin-top: 1rem; padding: 1rem; background: var(--light-color); border-radius: var(--border-radius);">
                                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($student['admission_no']); ?>
                                        </div>
                                        <small>Admission Number</small>
                                    </div>
                                </div>
                                
                                <div>
                                    <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </h2>
                                    
                                    <!-- Basic Information -->
                                    <div class="mb-3">
                                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Basic Information</h4>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                            <div><strong>First Name:</strong> <?php echo htmlspecialchars($student['first_name']); ?></div>
                                            <div><strong>Last Name:</strong> <?php echo htmlspecialchars($student['last_name']); ?></div>
                                            <div><strong>Father's Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></div>
                                            <div><strong>Mother's Name:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></div>
                                            <div><strong>Date of Birth:</strong> <?php echo date('d/m/Y', strtotime($student['date_of_birth'])); ?></div>
                                            <div><strong>Gender:</strong> <?php echo ucfirst($student['gender']); ?></div>
                                            <div><strong>Blood Group:</strong> <?php echo $student['blood_group'] ?: 'Not specified'; ?></div>
                                            <div><strong>Category:</strong> <?php echo $student['category'] ?: 'Not specified'; ?></div>
                                            <div><strong>Religion:</strong> <?php echo $student['religion'] ?: 'Not specified'; ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information -->
                                    <div class="mb-3">
                                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Contact Information</h4>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                            <div><strong>Student Mobile:</strong> <?php echo $student['mobile_no'] ?: 'Not provided'; ?></div>
                                            <div><strong>Parent Mobile:</strong> <?php echo htmlspecialchars($student['parent_mobile']); ?></div>
                                            <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?: $student['user_email']); ?></div>
                                            <div><strong>Village/City:</strong> <?php echo htmlspecialchars($student['village']); ?></div>
                                            <div><strong>Pincode:</strong> <?php echo htmlspecialchars($student['pincode'] ?: 'Not provided'); ?></div>
                                        </div>
                                        
                                        <?php if ($student['address']): ?>
                                            <div style="margin-top: 1rem;">
                                                <strong>Address:</strong><br>
                                                <div style="background: white; padding: 1rem; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                                                    <?php echo nl2br(htmlspecialchars($student['address'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Academic Information -->
                                    <div class="mb-3">
                                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Academic Information</h4>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                            <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></div>
                                            <div><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_no'] ?: 'Not assigned'); ?></div>
                                            <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($student['academic_year']); ?></div>
                                            <div><strong>Admission Date:</strong> <?php echo date('d/m/Y', strtotime($student['admission_date'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Government IDs -->
                                    <div class="mb-3">
                                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Government IDs</h4>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                            <div><strong>Aadhar Number:</strong> <?php echo $student['aadhar_no'] ? htmlspecialchars($student['aadhar_no']) : 'Not provided'; ?></div>
                                            <div><strong>Samagra ID:</strong> <?php echo $student['samagra_id'] ? htmlspecialchars($student['samagra_id']) : 'Not provided'; ?></div>
                                            <div><strong>PAN Number:</strong> <?php echo $student['pan_no'] ? htmlspecialchars($student['pan_no']) : 'Not provided'; ?></div>
                                            <div><strong>Scholar Number:</strong> <?php echo $student['scholar_no'] ? htmlspecialchars($student['scholar_no']) : 'Not provided'; ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Login Information -->
                                    <div class="mb-3">
                                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Login Information</h4>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                            <div><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></div>
                                            <div><strong>Account Created:</strong> <?php echo date('d/m/Y', strtotime($student['created_at'])); ?></div>
                                            <div><strong>Last Updated:</strong> <?php echo date('d/m/Y H:i', strtotime($student['updated_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="academics.php" class="btn btn-primary">
                                📚 View Academic Records
                            </a>
                            <a href="fees.php" class="btn btn-success">
                                💰 View Fee History
                            </a>
                            <a href="attendance.php" class="btn btn-warning">
                                📅 View Attendance
                            </a>
                            <a href="documents.php" class="btn btn-secondary">
                                📄 My Documents
                            </a>
                        </div>
                    </div>

                    <!-- Important Notice -->
                    <div class="alert alert-warning">
                        <strong>Note:</strong> If you notice any incorrect information in your profile, please contact the school administration for updates. Students cannot modify their own profile information.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup print functionality
            setupPrintFunctionality();
        });
    </script>
</body>
</html>
