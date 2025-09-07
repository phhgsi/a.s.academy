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
    $error = 'Student profile not found. Please contact administrator.';
} else {
    // Handle filter parameters
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Get attendance records for the specified month/year
    $stmt = $pdo->prepare("
        SELECT a.*, DATE_FORMAT(a.attendance_date, '%Y-%m-%d') as formatted_date,
               DAYNAME(a.attendance_date) as day_name
        FROM attendance a
        WHERE a.student_id = ? 
        AND MONTH(a.attendance_date) = ? 
        AND YEAR(a.attendance_date) = ?
        ORDER BY a.attendance_date DESC
    ");
    $stmt->execute([$student['id'], $month, $year]);
    $attendance_records = $stmt->fetchAll();
    
    // Calculate statistics
    $total_days = count($attendance_records);
    $present_days = count(array_filter($attendance_records, function($record) {
        return $record['status'] === 'present';
    }));
    $absent_days = count(array_filter($attendance_records, function($record) {
        return $record['status'] === 'absent';
    }));
    $late_days = count(array_filter($attendance_records, function($record) {
        return $record['status'] === 'late';
    }));
    
    $attendance_percentage = $total_days > 0 ? ($present_days / $total_days) * 100 : 0;
    
    // Get overall attendance statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_recorded_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as total_absent,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as total_late
        FROM attendance 
        WHERE student_id = ? AND YEAR(attendance_date) = ?
    ");
    $stmt->execute([$student['id'], $year]);
    $overall_stats = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">📅 My Attendance</h1>
                    <p class="page-subtitle">View your attendance records and statistics</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <!-- Filter Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Filter Attendance</h3>
                        </div>
                        <form method="GET" class="filter-form">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                                <div>
                                    <label>Month:</label>
                                    <select name="month" class="form-control">
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo $month == sprintf('%02d', $i) ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label>Year:</label>
                                    <select name="year" class="form-control">
                                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $year == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Overall Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 Attendance Statistics - <?php echo $year; ?></h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                            <div class="stat-card stat-total">
                                <div class="stat-value"><?php echo $overall_stats['total_recorded_days']; ?></div>
                                <div class="stat-label">Total Days Recorded</div>
                            </div>
                            <div class="stat-card stat-present">
                                <div class="stat-value"><?php echo $overall_stats['total_present']; ?></div>
                                <div class="stat-label">Present Days</div>
                            </div>
                            <div class="stat-card stat-absent">
                                <div class="stat-value"><?php echo $overall_stats['total_absent']; ?></div>
                                <div class="stat-label">Absent Days</div>
                            </div>
                            <div class="stat-card stat-late">
                                <div class="stat-value"><?php echo $overall_stats['total_late']; ?></div>
                                <div class="stat-label">Late Days</div>
                            </div>
                            <div class="stat-card stat-percentage">
                                <div class="stat-value">
                                    <?php echo $overall_stats['total_recorded_days'] > 0 ? number_format(($overall_stats['total_present'] / $overall_stats['total_recorded_days']) * 100, 1) : 0; ?>%
                                </div>
                                <div class="stat-label">Attendance Percentage</div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Attendance -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Monthly Attendance - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
                            <button class="btn btn-outline" onclick="printSection('monthlyAttendance')">🖨️ Print</button>
                        </div>
                        
                        <?php if (empty($attendance_records)): ?>
                            <div class="alert alert-info">
                                No attendance records found for <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>.
                            </div>
                        <?php else: ?>
                            <!-- Current Month Stats -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                                <div class="mini-stat-card stat-total">
                                    <div class="mini-stat-value"><?php echo $total_days; ?></div>
                                    <div class="mini-stat-label">Total Days</div>
                                </div>
                                <div class="mini-stat-card stat-present">
                                    <div class="mini-stat-value"><?php echo $present_days; ?></div>
                                    <div class="mini-stat-label">Present</div>
                                </div>
                                <div class="mini-stat-card stat-absent">
                                    <div class="mini-stat-value"><?php echo $absent_days; ?></div>
                                    <div class="mini-stat-label">Absent</div>
                                </div>
                                <div class="mini-stat-card stat-late">
                                    <div class="mini-stat-value"><?php echo $late_days; ?></div>
                                    <div class="mini-stat-label">Late</div>
                                </div>
                                <div class="mini-stat-card stat-percentage">
                                    <div class="mini-stat-value"><?php echo number_format($attendance_percentage, 1); ?>%</div>
                                    <div class="mini-stat-label">Percentage</div>
                                </div>
                            </div>
                            
                            <div id="monthlyAttendance">
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Day</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance_records as $record): ?>
                                                <tr class="attendance-row attendance-<?php echo $record['status']; ?>">
                                                    <td><?php echo date('d/m/Y', strtotime($record['attendance_date'])); ?></td>
                                                    <td><?php echo $record['day_name']; ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                                            <?php 
                                                            switch($record['status']) {
                                                                case 'present':
                                                                    echo '✅ Present';
                                                                    break;
                                                                case 'absent':
                                                                    echo '❌ Absent';
                                                                    break;
                                                                case 'late':
                                                                    echo '⏰ Late';
                                                                    break;
                                                                default:
                                                                    echo ucfirst($record['status']);
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['remarks'] ?: '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Attendance Guidelines -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📋 Attendance Guidelines</h3>
                        </div>
                        <div class="alert alert-info">
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <li><strong>Minimum Attendance Required:</strong> 75% for appearing in examinations</li>
                                <li><strong>Present:</strong> Student was on time and attended all classes</li>
                                <li><strong>Late:</strong> Student arrived late but attended classes</li>
                                <li><strong>Absent:</strong> Student did not attend school</li>
                                <li><strong>Medical Leave:</strong> Contact administration for medical absence documentation</li>
                            </ul>
                        </div>
                        
                        <?php if ($overall_stats['total_recorded_days'] > 0): ?>
                            <?php $yearly_percentage = ($overall_stats['total_present'] / $overall_stats['total_recorded_days']) * 100; ?>
                            <div class="mt-3">
                                <?php if ($yearly_percentage < 75): ?>
                                    <div class="alert alert-warning">
                                        <strong>⚠️ Warning:</strong> Your current attendance is <?php echo number_format($yearly_percentage, 1); ?>%, which is below the minimum requirement of 75%. Please ensure regular attendance to avoid academic issues.
                                    </div>
                                <?php elseif ($yearly_percentage >= 90): ?>
                                    <div class="alert alert-success">
                                        <strong>🎉 Excellent!</strong> Your attendance is <?php echo number_format($yearly_percentage, 1); ?>% - keep up the great work!
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <strong>👍 Good!</strong> Your attendance is <?php echo number_format($yearly_percentage, 1); ?>% - maintain this level for academic success.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <style>
    .status-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .status-present {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .status-absent {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .status-late {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .attendance-row.attendance-present {
        background-color: rgba(212, 237, 218, 0.3);
    }
    .attendance-row.attendance-absent {
        background-color: rgba(248, 215, 218, 0.3);
    }
    .attendance-row.attendance-late {
        background-color: rgba(255, 243, 205, 0.3);
    }

    .stat-card {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .mini-stat-card {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .stat-value, .mini-stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-total .stat-value, .stat-total .mini-stat-value { color: #6c757d; }
    .stat-present .stat-value, .stat-present .mini-stat-value { color: #28a745; }
    .stat-absent .stat-value, .stat-absent .mini-stat-value { color: #dc3545; }
    .stat-late .stat-value, .stat-late .mini-stat-value { color: #ffc107; }
    .stat-percentage .stat-value, .stat-percentage .mini-stat-value { color: var(--primary-color); }
    
    .stat-label, .mini-stat-label {
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .mini-stat-value {
        font-size: 1.5rem;
    }

    .filter-form {
        background: var(--light-color);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 0;
    }
    </style>
</body>
</html>
