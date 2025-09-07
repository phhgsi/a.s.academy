<?php
require_once '../config/database.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Get teacher's assigned classes
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(s.id) as student_count
    FROM classes c 
    LEFT JOIN students s ON c.id = s.class_id AND s.is_active = 1
    WHERE c.class_teacher_id = ? AND c.is_active = 1
    GROUP BY c.id
");
$stmt->execute([$_SESSION['user_id']]);
$assigned_classes = $stmt->fetchAll();

// Get teacher's subjects
$stmt = $pdo->prepare("
    SELECT sub.*, c.class_name, c.section
    FROM subjects sub 
    LEFT JOIN classes c ON sub.class_id = c.id
    WHERE sub.teacher_id = ? AND sub.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$assigned_subjects = $stmt->fetchAll();

// Get statistics
$stats = [];

// Total students under teacher
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT s.id) as count 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE c.class_teacher_id = ? AND s.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$stats['total_students'] = $stmt->fetch()['count'];

// Total classes assigned
$stats['total_classes'] = count($assigned_classes);

// Total subjects assigned
$stats['total_subjects'] = count($assigned_subjects);

// Today's attendance marked
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM attendance a 
    JOIN classes c ON a.class_id = c.id 
    WHERE c.class_teacher_id = ? AND DATE(a.attendance_date) = CURRENT_DATE()
");
$stmt->execute([$_SESSION['user_id']]);
$stats['todays_attendance'] = $stmt->fetch()['count'];

// Recent attendance records
$stmt = $pdo->prepare("
    SELECT a.*, s.first_name, s.last_name, s.admission_no, c.class_name, c.section
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    JOIN classes c ON a.class_id = c.id
    WHERE a.marked_by = ?
    ORDER BY a.attendance_date DESC, a.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recent_attendance = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - A.S.ACADEMY</title>
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
                    <h1 class="page-title">Teacher Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>

                <!-- Dashboard Statistics -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Total Students</div>
                            <div class="stat-icon" style="background: var(--primary-color);">👥</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-change">Under my supervision</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Assigned Classes</div>
                            <div class="stat-icon" style="background: var(--success-color);">🏫</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_classes']; ?></div>
                        <div class="stat-change">Class teacher</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Teaching Subjects</div>
                            <div class="stat-icon" style="background: var(--warning-color);">📚</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_subjects']; ?></div>
                        <div class="stat-change">Assigned subjects</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Today's Attendance</div>
                            <div class="stat-icon" style="background: var(--secondary-color);">📅</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['todays_attendance']; ?></div>
                        <div class="stat-change">Records marked</div>
                    </div>
                </div>

                <!-- My Classes and Subjects -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <!-- Assigned Classes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">My Classes</h3>
                            <a href="classes.php" class="btn btn-outline">Manage Classes</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Students</th>
                                        <th>Academic Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assigned_classes)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No classes assigned</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assigned_classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?></td>
                                                <td><?php echo $class['student_count']; ?></td>
                                                <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                                <td>
                                                    <a href="students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;">👥</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Teaching Subjects -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Teaching Subjects</h3>
                            <a href="academics.php" class="btn btn-outline">Manage Records</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Subject Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assigned_subjects)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No subjects assigned</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assigned_subjects as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['class_name'] . ' ' . $subject['section']); ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                <td>
                                                    <a href="academics.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;">📊</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Attendance Records</h3>
                        <a href="attendance.php" class="btn btn-outline">Mark Attendance</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_attendance)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No attendance records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_attendance as $attendance): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($attendance['attendance_date'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?><br>
                                                <small class="text-secondary"><?php echo htmlspecialchars($attendance['admission_no']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($attendance['class_name'] . ' ' . $attendance['section']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $attendance['status'] == 'present' ? 'success' : ($attendance['status'] == 'late' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($attendance['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($attendance['remarks'] ?: '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="attendance.php" class="btn btn-primary">
                            📅 Mark Attendance
                        </a>
                        <a href="academics.php" class="btn btn-success">
                            📊 Academic Records
                        </a>
                        <a href="students.php" class="btn btn-secondary">
                            👥 View Students
                        </a>
                        <a href="classes.php" class="btn btn-warning">
                            🏫 My Classes
                        </a>
                        <a href="reports.php" class="btn btn-outline">
                            📈 Generate Reports
                        </a>
                    </div>
                </div>
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
