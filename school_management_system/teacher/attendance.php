<?php
require_once '../config/database.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Get teacher information
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ? AND is_active = 1");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    $error = 'Teacher profile not found. Please contact administrator.';
} else {
    // Get teacher's assigned classes
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.class_name, c.section
        FROM class_subjects cs
        LEFT JOIN classes c ON cs.class_id = c.id
        WHERE cs.teacher_id = ?
        ORDER BY c.class_name, c.section
    ");
    $stmt->execute([$teacher['id']]);
    $assigned_classes = $stmt->fetchAll();
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $class_id = $_POST['class_id'];
    $attendance_data = $_POST['attendance'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Delete existing attendance for this date and class
        $stmt = $pdo->prepare("
            DELETE FROM attendance 
            WHERE attendance_date = ? AND student_id IN (
                SELECT id FROM students WHERE class_id = ? AND is_active = 1
            )
        ");
        $stmt->execute([$attendance_date, $class_id]);
        
        // Insert new attendance records
        foreach ($attendance_data as $student_id => $status) {
            $remarks = $_POST['remarks'][$student_id] ?? '';
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, attendance_date, status, remarks, marked_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $attendance_date, $status, $remarks, $_SESSION['user_id']]);
        }
        
        $pdo->commit();
        $success = 'Attendance marked successfully for ' . date('d/m/Y', strtotime($attendance_date));
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Error marking attendance: ' . $e->getMessage();
    }
}

// Get students for selected class
$students = [];
$selected_class = $_GET['class_id'] ?? ($_POST['class_id'] ?? '');
$attendance_date = $_GET['date'] ?? ($_POST['attendance_date'] ?? date('Y-m-d'));

if ($selected_class) {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               a.status as current_status, 
               a.remarks as current_remarks
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = ?
        WHERE s.class_id = ? AND s.is_active = 1
        ORDER BY s.roll_no, s.first_name
    ");
    $stmt->execute([$attendance_date, $selected_class]);
    $students = $stmt->fetchAll();
    
    // Get class info
    $stmt = $pdo->prepare("SELECT class_name, section FROM classes WHERE id = ?");
    $stmt->execute([$selected_class]);
    $class_info = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">📅 Mark Attendance</h1>
                    <p class="page-subtitle">Mark student attendance for your assigned classes</p>
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

                <?php if (empty($assigned_classes)): ?>
                    <div class="alert alert-warning">
                        You don't have any class assignments. Please contact the administrator to assign classes and subjects to your profile.
                    </div>
                <?php else: ?>
                    <!-- Class Selection -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Select Class & Date</h3>
                        </div>
                        <form method="GET" class="filter-form">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                                <div>
                                    <label>Class:</label>
                                    <select name="class_id" class="form-control" onchange="this.form.submit()" required>
                                        <option value="">-- Select Class --</option>
                                        <?php foreach ($assigned_classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label>Date:</label>
                                    <input type="date" name="date" class="form-control" value="<?php echo $attendance_date; ?>" onchange="this.form.submit()">
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">🔍 Load Students</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($selected_class && !empty($students)): ?>
                        <!-- Attendance Form -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Attendance for Class <?php echo htmlspecialchars($class_info['class_name'] . ' ' . $class_info['section']); ?> 
                                    - <?php echo date('d/m/Y', strtotime($attendance_date)); ?>
                                </h3>
                                <div class="card-actions">
                                    <button type="button" onclick="markAllPresent()" class="btn btn-success btn-sm">✅ Mark All Present</button>
                                    <button type="button" onclick="clearAll()" class="btn btn-outline btn-sm">🔄 Clear All</button>
                                </div>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                                <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
                                
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Admission No</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['roll_no'] ?: 'N/A'); ?></td>
                                                    <td>
                                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                            <?php if ($student['photo']): ?>
                                                                <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                                                     alt="Student Photo" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                                    <td>
                                                        <select name="attendance[<?php echo $student['id']; ?>]" class="form-control attendance-select" style="min-width: 120px;">
                                                            <option value="">-- Select --</option>
                                                            <option value="present" <?php echo ($student['current_status'] === 'present') ? 'selected' : ''; ?>>✅ Present</option>
                                                            <option value="absent" <?php echo ($student['current_status'] === 'absent') ? 'selected' : ''; ?>>❌ Absent</option>
                                                            <option value="late" <?php echo ($student['current_status'] === 'late') ? 'selected' : ''; ?>>⏰ Late</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="remarks[<?php echo $student['id']; ?>]" 
                                                               class="form-control" placeholder="Optional remarks" 
                                                               value="<?php echo htmlspecialchars($student['current_remarks'] ?: ''); ?>"
                                                               style="max-width: 200px;">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div style="margin-top: 2rem; text-align: center;">
                                    <button type="submit" name="mark_attendance" class="btn btn-primary" style="min-width: 200px;">
                                        💾 Save Attendance
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Attendance Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">📊 Today's Summary</h3>
                            </div>
                            <?php
                            $present_count = count(array_filter($students, function($s) { return $s['current_status'] === 'present'; }));
                            $absent_count = count(array_filter($students, function($s) { return $s['current_status'] === 'absent'; }));
                            $late_count = count(array_filter($students, function($s) { return $s['current_status'] === 'late'; }));
                            $total_students = count($students);
                            $marked_count = $present_count + $absent_count + $late_count;
                            $unmarked_count = $total_students - $marked_count;
                            ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                                <div class="mini-stat-card">
                                    <div class="mini-stat-value" style="color: #6c757d;"><?php echo $total_students; ?></div>
                                    <div class="mini-stat-label">Total Students</div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="mini-stat-value" style="color: #28a745;"><?php echo $present_count; ?></div>
                                    <div class="mini-stat-label">Present</div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="mini-stat-value" style="color: #dc3545;"><?php echo $absent_count; ?></div>
                                    <div class="mini-stat-label">Absent</div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="mini-stat-value" style="color: #ffc107;"><?php echo $late_count; ?></div>
                                    <div class="mini-stat-label">Late</div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="mini-stat-value" style="color: #17a2b8;"><?php echo $unmarked_count; ?></div>
                                    <div class="mini-stat-label">Unmarked</div>
                                </div>
                                <div class="mini-stat-card">
                                    <div class="mini-stat-value" style="color: var(--primary-color);">
                                        <?php echo $total_students > 0 ? number_format(($present_count / $total_students) * 100, 1) : 0; ?>%
                                    </div>
                                    <div class="mini-stat-label">Attendance Rate</div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($selected_class): ?>
                        <div class="alert alert-warning">
                            No students found in the selected class for <?php echo date('d/m/Y', strtotime($attendance_date)); ?>.
                        </div>
                    <?php endif; ?>

                    <!-- Recent Attendance -->
                    <?php if ($selected_class): ?>
                        <?php
                        // Get recent attendance for this class
                        $stmt = $pdo->prepare("
                            SELECT attendance_date, 
                                   COUNT(*) as total_students,
                                   SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                                   SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                                   SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                            FROM attendance a
                            JOIN students s ON a.student_id = s.id
                            WHERE s.class_id = ? 
                            AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                            GROUP BY attendance_date
                            ORDER BY attendance_date DESC
                            LIMIT 7
                        ");
                        $stmt->execute([$selected_class]);
                        $recent_attendance = $stmt->fetchAll();
                        ?>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">📈 Recent Attendance (Last 7 Days)</h3>
                            </div>
                            
                            <?php if (empty($recent_attendance)): ?>
                                <div class="alert alert-info">
                                    No recent attendance records found for this class.
                                </div>
                            <?php else: ?>
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Total Students</th>
                                                <th>Present</th>
                                                <th>Absent</th>
                                                <th>Late</th>
                                                <th>Attendance %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_attendance as $record): ?>
                                                <?php $percentage = ($record['present_count'] / $record['total_students']) * 100; ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y (D)', strtotime($record['attendance_date'])); ?></td>
                                                    <td><?php echo $record['total_students']; ?></td>
                                                    <td><span style="color: #28a745; font-weight: 600;"><?php echo $record['present_count']; ?></span></td>
                                                    <td><span style="color: #dc3545; font-weight: 600;"><?php echo $record['absent_count']; ?></span></td>
                                                    <td><span style="color: #ffc107; font-weight: 600;"><?php echo $record['late_count']; ?></span></td>
                                                    <td>
                                                        <span style="color: <?php echo $percentage >= 80 ? '#28a745' : ($percentage >= 60 ? '#ffc107' : '#dc3545'); ?>; font-weight: 600;">
                                                            <?php echo number_format($percentage, 1); ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Instructions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📋 Instructions</h3>
                        </div>
                        <div class="alert alert-info">
                            <h5>How to mark attendance:</h5>
                            <ul style="margin-bottom: 0;">
                                <li><strong>Step 1:</strong> Select the class and date from the form above</li>
                                <li><strong>Step 2:</strong> Mark each student's status (Present/Absent/Late)</li>
                                <li><strong>Step 3:</strong> Add remarks if necessary (e.g., medical leave, late reason)</li>
                                <li><strong>Step 4:</strong> Click "Save Attendance" to submit</li>
                                <li><strong>Quick Tip:</strong> Use "Mark All Present" button to quickly mark everyone present, then adjust individual students as needed</li>
                                <li><strong>Note:</strong> You can modify attendance for any date, not just today</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <style>
    .mini-stat-card {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .mini-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .mini-stat-label {
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .filter-form {
        background: var(--light-color);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 0;
    }

    .attendance-select {
        width: 120px;
    }

    @media (max-width: 768px) {
        .attendance-select {
            width: 100%;
            min-width: auto;
        }
    }
    </style>

    <script>
    function markAllPresent() {
        const selects = document.querySelectorAll('.attendance-select');
        selects.forEach(select => {
            select.value = 'present';
        });
    }

    function clearAll() {
        const selects = document.querySelectorAll('.attendance-select');
        const remarks = document.querySelectorAll('input[name^="remarks"]');
        
        selects.forEach(select => {
            select.value = '';
        });
        
        remarks.forEach(remark => {
            remark.value = '';
        });
    }

    // Auto-save functionality (optional)
    let saveTimeout;
    function autoSave() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            // Could implement auto-save here if needed
            console.log('Auto-save triggered');
        }, 2000);
    }

    // Add event listeners for auto-save
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.attendance-select');
        const remarks = document.querySelectorAll('input[name^="remarks"]');
        
        [...selects, ...remarks].forEach(element => {
            element.addEventListener('change', autoSave);
        });
    });
    </script>
</body>
</html>
