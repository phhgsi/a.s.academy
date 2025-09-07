<?php
require_once '../config/database.php';

// Check if user is admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_attendance'])) {
        try {
            $pdo->beginTransaction();
            
            $class_id = $_POST['class_id'];
            $attendance_date = $_POST['attendance_date'];
            $students = $_POST['students'] ?? [];
            
            // Check if attendance already marked for this class and date
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE class_id = ? AND attendance_date = ?");
            $stmt->execute([$class_id, $attendance_date]);
            $existing_count = $stmt->fetch()['count'];
            
            if ($existing_count > 0) {
                // Update existing attendance
                foreach ($students as $student_id => $data) {
                    $stmt = $pdo->prepare("
                        UPDATE attendance 
                        SET status = ?, remarks = ?, marked_by = ?, updated_at = NOW()
                        WHERE student_id = ? AND class_id = ? AND attendance_date = ?
                    ");
                    $stmt->execute([
                        $data['status'],
                        $data['remarks'] ?? '',
                        $_SESSION['user_id'],
                        $student_id,
                        $class_id,
                        $attendance_date
                    ]);
                }
                $success_message = "Attendance updated successfully for " . date('d/m/Y', strtotime($attendance_date));
            } else {
                // Insert new attendance records
                foreach ($students as $student_id => $data) {
                    $stmt = $pdo->prepare("
                        INSERT INTO attendance (student_id, class_id, attendance_date, status, remarks, marked_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $student_id,
                        $class_id,
                        $attendance_date,
                        $data['status'],
                        $data['remarks'] ?? '',
                        $_SESSION['user_id']
                    ]);
                }
                $success_message = "Attendance marked successfully for " . date('d/m/Y', strtotime($attendance_date));
            }
            
            $pdo->commit();
            
            // Return JSON response for AJAX requests
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $success_message]);
                exit();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'Error marking attendance: ' . $e->getMessage();
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            }
        }
    }
}

// Get classes for selection
$stmt = $pdo->prepare("SELECT * FROM classes WHERE is_active = 1 ORDER BY class_name, section");
$stmt->execute();
$classes = $stmt->fetchAll();

// Get selected class students
$selected_class_id = $_GET['class_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$students = [];
$existing_attendance = [];

if ($selected_class_id) {
    // Get students in the class
    $stmt = $pdo->prepare("
        SELECT s.* FROM students s 
        WHERE s.class_id = ? AND s.is_active = 1 
        ORDER BY s.first_name, s.last_name
    ");
    $stmt->execute([$selected_class_id]);
    $students = $stmt->fetchAll();
    
    // Get existing attendance for the date
    $stmt = $pdo->prepare("
        SELECT student_id, status, remarks 
        FROM attendance 
        WHERE class_id = ? AND attendance_date = ?
    ");
    $stmt->execute([$selected_class_id, $selected_date]);
    $attendance_data = $stmt->fetchAll();
    
    foreach ($attendance_data as $record) {
        $existing_attendance[$record['student_id']] = [
            'status' => $record['status'],
            'remarks' => $record['remarks']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - A.S.ACADEMY</title>
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
                    <h1 class="page-title">Mark Attendance</h1>
                    <p class="page-subtitle">Record student attendance for classes</p>
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

                <!-- Class and Date Selection -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Select Class and Date</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <form method="GET" class="form-row">
                            <div class="form-group">
                                <label for="class_id">Select Class:</label>
                                <select name="class_id" id="class_id" class="form-control" required onchange="this.form.submit()">
                                    <option value="">Choose a class...</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $selected_class_id == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date">Attendance Date:</label>
                                <input type="date" name="date" id="date" class="form-control" 
                                       value="<?php echo $selected_date; ?>" 
                                       max="<?php echo date('Y-m-d'); ?>"
                                       onchange="this.form.submit()">
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_class_id && !empty($students)): ?>
                <!-- Attendance Marking Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Mark Attendance - 
                            <?php 
                            $class_info = array_filter($classes, function($c) use ($selected_class_id) { 
                                return $c['id'] == $selected_class_id; 
                            });
                            $class_info = reset($class_info);
                            echo htmlspecialchars($class_info['class_name'] . ' - ' . $class_info['section']);
                            ?>
                        </h3>
                        <div>
                            <button type="button" class="btn btn-success" onclick="markAllPresent()">
                                <i class="bi bi-check-all"></i> Mark All Present
                            </button>
                            <button type="button" class="btn btn-warning" onclick="markAllAbsent()">
                                <i class="bi bi-x-circle"></i> Mark All Absent
                            </button>
                        </div>
                    </div>

                    <form method="POST" id="attendanceForm" class="ajax-form">
                        <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Admission No</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <?php 
                                        $current_status = $existing_attendance[$student['id']]['status'] ?? 'present';
                                        $current_remarks = $existing_attendance[$student['id']]['remarks'] ?? '';
                                        ?>
                                        <tr class="student-row">
                                            <td>
                                                <div class="student-info">
                                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                    <?php if ($student['photo']): ?>
                                                        <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                                             alt="Photo" class="student-thumb">
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                            <td>
                                                <div class="status-buttons">
                                                    <label class="status-option present <?php echo $current_status === 'present' ? 'active' : ''; ?>">
                                                        <input type="radio" name="students[<?php echo $student['id']; ?>][status]" 
                                                               value="present" <?php echo $current_status === 'present' ? 'checked' : ''; ?>>
                                                        <span>Present</span>
                                                    </label>
                                                    <label class="status-option absent <?php echo $current_status === 'absent' ? 'active' : ''; ?>">
                                                        <input type="radio" name="students[<?php echo $student['id']; ?>][status]" 
                                                               value="absent" <?php echo $current_status === 'absent' ? 'checked' : ''; ?>>
                                                        <span>Absent</span>
                                                    </label>
                                                    <label class="status-option late <?php echo $current_status === 'late' ? 'active' : ''; ?>">
                                                        <input type="radio" name="students[<?php echo $student['id']; ?>][status]" 
                                                               value="late" <?php echo $current_status === 'late' ? 'checked' : ''; ?>>
                                                        <span>Late</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="students[<?php echo $student['id']; ?>][remarks]" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="Add remarks..."
                                                       value="<?php echo htmlspecialchars($current_remarks); ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="padding: 1.5rem; text-align: center; border-top: 1px solid #e2e8f0;">
                            <button type="submit" name="mark_attendance" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Attendance
                            </button>
                            <a href="attendance.php" class="btn btn-outline">
                                <i class="bi bi-arrow-left"></i> Back to Attendance
                            </a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
    <script>
        // Status button functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Setup status button interactions
            const statusOptions = document.querySelectorAll('.status-option');
            statusOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const row = this.closest('.student-row');
                    const statusButtons = row.querySelectorAll('.status-option');
                    
                    // Remove active class from all buttons in this row
                    statusButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                });
            });
            
            // Setup form submission
            const form = document.getElementById('attendanceForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitFormWithLoading(form, function(data) {
                        showNotification(data.message, 'success');
                        // Optionally redirect or refresh
                        setTimeout(() => {
                            window.location.href = 'attendance.php';
                        }, 2000);
                    });
                });
            }
        });
        
        function markAllPresent() {
            const presentRadios = document.querySelectorAll('input[value="present"]');
            presentRadios.forEach(radio => {
                radio.checked = true;
                radio.closest('.status-option').classList.add('active');
                radio.closest('.student-row').querySelectorAll('.status-option').forEach(btn => {
                    if (!btn.classList.contains('present')) {
                        btn.classList.remove('active');
                    }
                });
            });
            showNotification('All students marked as present', 'info');
        }
        
        function markAllAbsent() {
            const absentRadios = document.querySelectorAll('input[value="absent"]');
            absentRadios.forEach(radio => {
                radio.checked = true;
                radio.closest('.status-option').classList.add('active');
                radio.closest('.student-row').querySelectorAll('.status-option').forEach(btn => {
                    if (!btn.classList.contains('absent')) {
                        btn.classList.remove('active');
                    }
                });
            });
            showNotification('All students marked as absent', 'warning');
        }
    </script>

    <style>
        .student-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .student-thumb {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .status-option {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-option input {
            display: none;
        }
        
        .status-option.present {
            color: #065f46;
            border-color: #a7f3d0;
        }
        
        .status-option.present.active {
            background: #d1fae5;
            border-color: #10b981;
        }
        
        .status-option.absent {
            color: #991b1b;
            border-color: #fecaca;
        }
        
        .status-option.absent.active {
            background: #fee2e2;
            border-color: #ef4444;
        }
        
        .status-option.late {
            color: #92400e;
            border-color: #fde68a;
        }
        
        .status-option.late.active {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        
        .form-control-sm {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .student-row:hover {
            background: #f8fafc;
        }
    </style>
</body>
</html>
