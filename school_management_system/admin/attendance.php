<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Get attendance records
try {
    $stmt = $pdo->prepare("
        SELECT a.*, s.first_name, s.last_name, s.admission_no, c.class_name, c.section, u.full_name as marked_by_name 
        FROM attendance a 
        JOIN students s ON a.student_id = s.id 
        LEFT JOIN classes c ON a.class_id = c.id 
        LEFT JOIN users u ON a.marked_by = u.id 
        ORDER BY a.attendance_date DESC, c.class_name, s.first_name 
        LIMIT 50
    ");
    $stmt->execute();
    $attendance_records = $stmt->fetchAll();
} catch (Exception $e) {
    $attendance_records = [];
    $error_message = 'Error fetching attendance records: ' . $e->getMessage();
}

// Get today's attendance summary
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_marked,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM attendance 
        WHERE attendance_date = CURDATE()
    ");
    $stmt->execute();
    $today_stats = $stmt->fetch();
} catch (Exception $e) {
    $today_stats = ['total_marked' => 0, 'present_count' => 0, 'absent_count' => 0, 'late_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - A.S.ACADEMY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Attendance Management</h1>
                    <p class="page-subtitle">Monitor and manage student attendance</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success mb-4">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Today's Attendance Summary -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Total Marked</div>
                            <div class="stat-icon" style="background: var(--primary-color);"><i class="bi bi-clipboard-check"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $today_stats['total_marked']; ?></div>
                        <div class="stat-change">Today's attendance</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Present</div>
                            <div class="stat-icon" style="background: var(--success-color);"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $today_stats['present_count']; ?></div>
                        <div class="stat-change positive">Students present</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Absent</div>
                            <div class="stat-icon" style="background: var(--danger-color);"><i class="bi bi-x-circle-fill"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $today_stats['absent_count']; ?></div>
                        <div class="stat-change negative">Students absent</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Late</div>
                            <div class="stat-icon" style="background: var(--warning-color);"><i class="bi bi-clock-fill"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $today_stats['late_count']; ?></div>
                        <div class="stat-change">Students late</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Attendance Records</h3>
                        <div class="d-flex gap-1">
                            <div class="export-btn-group">
                                <button class="btn btn-outline export-btn" data-format="csv" data-page="attendance_export">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                </button>
                                <button class="btn btn-outline export-btn" data-format="pdf" data-page="attendance_export">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </button>
                            </div>
                            <button class="btn btn-outline" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <a href="mark_attendance.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Mark Attendance
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                    <th>Marked By</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendance_records)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No attendance records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($record['attendance_date'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($record['admission_no']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($record['class_name']) {
                                                    echo htmlspecialchars($record['class_name']);
                                                    if ($record['section']) {
                                                        echo ' - ' . htmlspecialchars($record['section']);
                                                    }
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($record['status']) {
                                                    case 'present':
                                                        $status_class = 'badge-success';
                                                        break;
                                                    case 'absent':
                                                        $status_class = 'badge-danger';
                                                        break;
                                                    case 'late':
                                                        $status_class = 'badge-warning';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($record['status']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['marked_by_name'] ?? 'System'); ?></td>
                                            <td><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/export.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add print functionality
            setupPrintFunctionality();
        });
    </script>
</body>
</html>
