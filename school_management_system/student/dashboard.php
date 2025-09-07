<?php
require_once '../config/database.php';

// Check if user is student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student information
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.user_id = ? AND s.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    $message = 'Student profile not found. Please contact administrator.';
} else {
    // Get fee payment summary
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total_paid,
            COUNT(*) as payment_count
        FROM fee_payments 
        WHERE student_id = ? AND YEAR(payment_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$student['id']]);
    $fee_summary = $stmt->fetch();

    // Get recent fee payments
    $stmt = $pdo->prepare("
        SELECT * FROM fee_payments 
        WHERE student_id = ? 
        ORDER BY payment_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$student['id']]);
    $recent_payments = $stmt->fetchAll();

    // Get academic records
    $stmt = $pdo->prepare("
        SELECT ar.*, s.subject_name 
        FROM academic_records ar 
        JOIN subjects s ON ar.subject_id = s.id 
        WHERE ar.student_id = ? 
        ORDER BY ar.exam_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$student['id']]);
    $academic_records = $stmt->fetchAll();

    // Get attendance summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
        FROM attendance 
        WHERE student_id = ? AND MONTH(attendance_date) = MONTH(CURRENT_DATE()) AND YEAR(attendance_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$student['id']]);
    $attendance_summary = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - A.S.ACADEMY</title>
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
                <?php if (!$student): ?>
                    <div class="alert alert-danger">
                        <?php echo $message; ?>
                    </div>
                <?php else: ?>
                    <div class="page-header">
                        <h1 class="page-title">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h1>
                        <p class="page-subtitle">Student Dashboard - <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></p>
                    </div>

                    <!-- Student Overview Cards -->
                    <div class="dashboard-grid">
                        <div class="stat-card">
                            <div class="stat-header d-flex justify-between align-center">
                                <div class="stat-title">Fees Paid This Year</div>
                                <div class="stat-icon" style="background: var(--success-color);">💰</div>
                            </div>
                            <div class="stat-value">₹<?php echo number_format($fee_summary['total_paid'], 2); ?></div>
                            <div class="stat-change"><?php echo $fee_summary['payment_count']; ?> payments</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header d-flex justify-between align-center">
                                <div class="stat-title">Current Attendance</div>
                                <div class="stat-icon" style="background: var(--primary-color);">📅</div>
                            </div>
                            <div class="stat-value"><?php echo $attendance_summary['attendance_percentage'] ?: '0'; ?>%</div>
                            <div class="stat-change"><?php echo $attendance_summary['present_days'] ?: '0'; ?>/<?php echo $attendance_summary['total_days'] ?: '0'; ?> days</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header d-flex justify-between align-center">
                                <div class="stat-title">Class</div>
                                <div class="stat-icon" style="background: var(--warning-color);">🏫</div>
                            </div>
                            <div class="stat-value"><?php echo htmlspecialchars($student['class_name']); ?></div>
                            <div class="stat-change"><?php echo htmlspecialchars($student['section']); ?></div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header d-flex justify-between align-center">
                                <div class="stat-title">Admission Number</div>
                                <div class="stat-icon" style="background: var(--secondary-color);">🎓</div>
                            </div>
                            <div class="stat-value" style="font-size: 1.5rem;"><?php echo htmlspecialchars($student['admission_no']); ?></div>
                            <div class="stat-change">Student ID</div>
                        </div>
                    </div>

                    <!-- Student Profile Summary -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <!-- Profile Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">My Profile</h3>
                                <a href="profile.php" class="btn btn-outline">View Full Profile</a>
                            </div>
                            
                            <div style="display: flex; gap: 1.5rem;">
                                <div class="text-center">
                                    <?php if ($student['photo']): ?>
                                        <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                             alt="Student Photo" class="photo-preview" style="width: 120px; height: 120px;">
                                    <?php else: ?>
                                        <div style="width: 120px; height: 120px; background: var(--primary-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 600;">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="flex: 1;">
                                    <div style="display: grid; gap: 0.5rem;">
                                        <div><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                        <div><strong>Father:</strong> <?php echo htmlspecialchars($student['father_name']); ?></div>
                                        <div><strong>Mother:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></div>
                                        <div><strong>DOB:</strong> <?php echo date('d/m/Y', strtotime($student['date_of_birth'])); ?></div>
                                        <div><strong>Mobile:</strong> <?php echo htmlspecialchars($student['mobile_no'] ?: 'Not provided'); ?></div>
                                        <div><strong>Village:</strong> <?php echo htmlspecialchars($student['village']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Academic Records</h3>
                                <a href="academics.php" class="btn btn-outline">View All</a>
                            </div>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Exam Type</th>
                                            <th>Marks</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($academic_records)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No academic records found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($academic_records as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['exam_type']); ?></td>
                                                    <td><?php echo $record['marks_obtained'] . '/' . $record['total_marks']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo ($record['grade'] == 'A' || $record['grade'] == 'A+') ? 'success' : (($record['grade'] == 'B') ? 'warning' : 'danger'); ?>">
                                                            <?php echo $record['grade']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Fee Payments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Fee Payments</h3>
                            <a href="fees.php" class="btn btn-outline">View All Payments</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Payment Date</th>
                                        <th>Academic Year</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_payments)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No payments found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $payment['payment_method'] == 'cash' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst($payment['payment_method']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Access</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="profile.php" class="btn btn-primary">
                                👤 View Full Profile
                            </a>
                            <a href="academics.php" class="btn btn-success">
                                📚 Academic Records
                            </a>
                            <a href="fees.php" class="btn btn-warning">
                                💰 Fee History
                            </a>
                            <a href="attendance.php" class="btn btn-secondary">
                                📅 Attendance Records
                            </a>
                            <a href="documents.php" class="btn btn-outline">
                                📄 My Documents
                            </a>
                        </div>
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
            // Add print buttons to dashboard tables
            setupPrintFunctionality();
        });
    </script>
</body>
</html>
