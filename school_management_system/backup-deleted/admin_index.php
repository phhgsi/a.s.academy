<?php
require_once '../config/database.php';
require_once '../includes/academic_year.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$current_academic_year = getCurrentAcademicYear();

// Get dashboard statistics for current academic year
$stats = [];

try {
    // Total students for current academic year
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE is_active = 1 AND academic_year = ?");
    $stmt->execute([$current_academic_year]);
    $stats['total_students'] = $stmt->fetchColumn();

    // Total teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE is_active = 1");
    $stmt->execute();
    $stats['total_teachers'] = $stmt->fetchColumn();

    // Total classes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE is_active = 1");
    $stmt->execute();
    $stats['total_classes'] = $stmt->fetchColumn();

    // Today's attendance
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'present'");
    $stmt->execute();
    $stats['today_present'] = $stmt->fetchColumn();

} catch (Exception $e) {
    // Set default values if queries fail
    $stats = [
        'total_students' => 0,
        'total_teachers' => 0,
        'total_classes' => 0,
        'today_present' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Overview of school management for Academic Year <?php echo $current_academic_year; ?></p>
                </div>

                <!-- Academic Year Change Success Message -->
                <?php if (isset($_GET['academic_year_changed'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        Academic year updated successfully to <?php echo $current_academic_year; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card bg-primary">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_students']); ?></h3>
                            <p>Total Students</p>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-success">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_teachers']); ?></h3>
                            <p>Teachers</p>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-info">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_classes']); ?></h3>
                            <p>Classes</p>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card bg-warning">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['today_present']); ?></h3>
                            <p>Present Today</p>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-lightning-charge"></i>
                            Quick Actions
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="students.php?action=add" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i>
                                Add New Student
                            </a>
                            <a href="fees.php" class="btn btn-success">
                                <i class="bi bi-currency-rupee"></i>
                                Collect Fees
                            </a>
                            <a href="attendance.php" class="btn btn-info">
                                <i class="bi bi-calendar-check"></i>
                                Mark Attendance
                            </a>
                            <a href="reports.php" class="btn btn-warning">
                                <i class="bi bi-graph-up"></i>
                                Generate Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Dashboard specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded successfully');
        });
    </script>
</body>
</html>
?>
