<?php
require_once '../config/database.php';
require_once '../includes/academic_year.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get student ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: students_list.php');
    exit();
}

$student_id = $_GET['id'];

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.id = ? AND s.is_active = 1
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['success_message'] = 'Student not found!';
    $_SESSION['success_type'] = 'error';
    header('Location: students_list.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
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
                        <h1 class="page-title">Student Details</h1>
                        <p class="page-subtitle"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                    </div>
                    <div class="d-flex gap-1 align-center">
                        <a href="students_list.php" class="btn btn-secondary">← Back to List</a>
                        <a href="students_edit.php?id=<?php echo $student['id']; ?>" class="btn btn-primary">✏️ Edit</a>
                        <button class="btn btn-outline" onclick="printSection('studentDetails')">🖨️ Print</button>
                    </div>
                </div>

                <!-- Student Details View -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Student Information</h3>
                        <div class="d-flex gap-1">
                            <span class="badge badge-success">Active</span>
                        </div>
                    </div>
                    
                    <div id="studentDetails">
                        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem; padding: 2rem;">
                            <div class="text-center">
                                <?php if ($student['photo']): ?>
                                    <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                         alt="Student Photo" class="photo-preview" style="width: 180px; height: 180px;">
                                <?php else: ?>
                                    <div style="width: 180px; height: 180px; background: var(--primary-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 600;">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                                    <div><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></div>
                                    <div><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                    <div><strong>Father's Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></div>
                                    <div><strong>Mother's Name:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></div>
                                    <div><strong>Date of Birth:</strong> <?php echo date('d/m/Y', strtotime($student['date_of_birth'])); ?></div>
                                    <div><strong>Gender:</strong> <?php echo ucfirst($student['gender']); ?></div>
                                    <div><strong>Blood Group:</strong> <?php echo $student['blood_group'] ?: 'Not specified'; ?></div>
                                    <div><strong>Category:</strong> <?php echo $student['category'] ?: 'Not specified'; ?></div>
                                    <div><strong>Religion:</strong> <?php echo $student['religion'] ?: 'Not specified'; ?></div>
                                    <div><strong>Mobile:</strong> <?php echo $student['mobile_no'] ?: 'Not provided'; ?></div>
                                    <div><strong>Parent Mobile:</strong> <?php echo $student['parent_mobile']; ?></div>
                                    <div><strong>Email:</strong> <?php echo $student['email'] ?: 'Not provided'; ?></div>
                                    <div><strong>Village:</strong> <?php echo $student['village']; ?></div>
                                    <div><strong>Pincode:</strong> <?php echo $student['pincode'] ?: 'Not provided'; ?></div>
                                    <div><strong>Class:</strong> <?php echo $student['class_name'] . ' ' . $student['section']; ?></div>
                                    <div><strong>Academic Year:</strong> <?php echo $student['academic_year']; ?></div>
                                    <div><strong>Admission Date:</strong> <?php echo date('d/m/Y', strtotime($student['admission_date'])); ?></div>
                                    <div><strong>Aadhar No:</strong> <?php echo $student['aadhar_no'] ?: 'Not provided'; ?></div>
                                    <div><strong>Samagra ID:</strong> <?php echo $student['samagra_id'] ?: 'Not provided'; ?></div>
                                    <div><strong>PAN No:</strong> <?php echo $student['pan_no'] ?: 'Not provided'; ?></div>
                                    <div><strong>Scholar No:</strong> <?php echo $student['scholar_no'] ?: 'Not provided'; ?></div>
                                </div>
                                
                                <?php if ($student['address']): ?>
                                    <div style="margin-top: 2rem;">
                                        <strong>Address:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($student['address'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/modern-ui.js"></script>

    <script>
        function printSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Student Details - ${document.title}</title>
                            <link rel="stylesheet" href="../assets/css/print.css">
                            <style>
                                body { font-family: Arial, sans-serif; }
                                .card { border: 1px solid #ddd; margin: 20px; padding: 20px; }
                                .card-header { border-bottom: 2px solid #007bff; margin-bottom: 20px; padding-bottom: 10px; }
                                img { max-width: 180px; height: auto; }
                            </style>
                        </head>
                        <body>
                            ${section.innerHTML}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }
    </script>
</body>
</html>
