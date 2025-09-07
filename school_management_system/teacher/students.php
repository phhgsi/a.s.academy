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

// Handle search and filter
$search = $_GET['search'] ?? '';
$class_filter = $_GET['class_id'] ?? '';

// Build query for students
$students = [];
if (!empty($assigned_classes)) {
    $class_ids = array_column($assigned_classes, 'id');
    $class_ids_placeholder = str_repeat('?,', count($class_ids) - 1) . '?';
    
    $sql = "
        SELECT s.*, c.class_name, c.section,
               (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND a.status = 'present') as present_count,
               (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id) as total_attendance,
               (SELECT AVG(ar.marks_obtained/ar.total_marks * 100) FROM academic_records ar WHERE ar.student_id = s.id) as avg_percentage
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE s.class_id IN ($class_ids_placeholder) AND s.is_active = 1
    ";
    
    $params = $class_ids;
    
    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ? OR s.roll_no LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    // Add class filter
    if (!empty($class_filter)) {
        $sql .= " AND s.class_id = ?";
        $params[] = $class_filter;
    }
    
    $sql .= " ORDER BY c.class_name, c.section, s.roll_no, s.first_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Panel</title>
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
                    <h1 class="page-title">👥 My Students</h1>
                    <p class="page-subtitle">View students from your assigned classes</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php elseif (empty($assigned_classes)): ?>
                    <div class="alert alert-warning">
                        You don't have any class assignments. Please contact the administrator to assign classes to your profile.
                    </div>
                <?php else: ?>
                    <!-- Search and Filter -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🔍 Search & Filter Students</h3>
                        </div>
                        <form method="GET" class="filter-form">
                            <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: end;">
                                <div>
                                    <label>Search Students:</label>
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by name, admission no, or roll no..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div>
                                    <label>Filter by Class:</label>
                                    <select name="class_id" class="form-control">
                                        <option value="">All My Classes</option>
                                        <?php foreach ($assigned_classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                                </div>
                                <div>
                                    <a href="students.php" class="btn btn-outline">🔄 Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Students List -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                📚 Students List 
                                <?php if (!empty($students)): ?>
                                    <span class="text-muted">(<?php echo count($students); ?> students)</span>
                                <?php endif; ?>
                            </h3>
                            <?php if (!empty($students)): ?>
                                <button class="btn btn-outline" onclick="printSection('studentsList')">🖨️ Print List</button>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($students)): ?>
                            <div class="alert alert-info">
                                <?php if (empty($search) && empty($class_filter)): ?>
                                    No students found in your assigned classes.
                                <?php else: ?>
                                    No students found matching your search criteria. Try adjusting your search terms or filters.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div id="studentsList">
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>Name</th>
                                                <th>Admission No</th>
                                                <th>Roll No</th>
                                                <th>Class</th>
                                                <th>Contact</th>
                                                <th>Attendance</th>
                                                <th>Performance</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($student['photo']): ?>
                                                            <img src="../uploads/photos/<?php echo $student['photo']; ?>" 
                                                                 alt="Student Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                                <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                            <br><small class="text-muted">Father: <?php echo htmlspecialchars($student['father_name']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['roll_no'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                                    <td>
                                                        <?php if ($student['mobile_no']): ?>
                                                            <div><?php echo htmlspecialchars($student['mobile_no']); ?></div>
                                                        <?php endif; ?>
                                                        <div><?php echo htmlspecialchars($student['parent_mobile']); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $attendance_percentage = $student['total_attendance'] > 0 ? 
                                                            ($student['present_count'] / $student['total_attendance']) * 100 : 0;
                                                        ?>
                                                        <div style="text-align: center;">
                                                            <div style="font-weight: 600; color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                                                <?php echo number_format($attendance_percentage, 1); ?>%
                                                            </div>
                                                            <small class="text-muted"><?php echo $student['present_count']; ?>/<?php echo $student['total_attendance']; ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($student['avg_percentage']): ?>
                                                            <div style="text-align: center;">
                                                                <div style="font-weight: 600; color: <?php echo $student['avg_percentage'] >= 70 ? '#28a745' : ($student['avg_percentage'] >= 50 ? '#ffc107' : '#dc3545'); ?>">
                                                                    <?php echo number_format($student['avg_percentage'], 1); ?>%
                                                                </div>
                                                                <small class="text-muted">Average</small>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No records</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div style="display: flex; gap: 0.25rem;">
                                                            <button type="button" onclick="viewStudent(<?php echo $student['id']; ?>)" 
                                                                    class="btn btn-outline btn-sm" title="View Details">👁️</button>
                                                            <a href="attendance.php?class_id=<?php echo $student['class_id']; ?>&date=<?php echo date('Y-m-d'); ?>" 
                                                               class="btn btn-primary btn-sm" title="Mark Attendance">📅</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Class Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 Class Summary</h3>
                        </div>
                        
                        <?php
                        // Calculate summary statistics
                        $class_summary = [];
                        foreach ($assigned_classes as $class) {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as total_students,
                                       AVG(CASE WHEN total_attendance > 0 THEN (present_count/total_attendance)*100 ELSE 0 END) as avg_attendance
                                FROM (
                                    SELECT s.id,
                                           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND a.status = 'present') as present_count,
                                           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id) as total_attendance
                                    FROM students s 
                                    WHERE s.class_id = ? AND s.is_active = 1
                                ) as attendance_stats
                            ");
                            $stmt->execute([$class['id']]);
                            $stats = $stmt->fetch();
                            
                            $class_summary[] = [
                                'class_name' => $class['class_name'] . ' ' . $class['section'],
                                'total_students' => $stats['total_students'],
                                'avg_attendance' => $stats['avg_attendance'] ?: 0
                            ];
                        }
                        ?>
                        
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach ($class_summary as $summary): ?>
                                <div style="background: var(--light-color); padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                                    <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: center;">
                                        <div>
                                            <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                                📖 Class <?php echo htmlspecialchars($summary['class_name']); ?>
                                            </h4>
                                        </div>
                                        <div class="text-center">
                                            <div style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">
                                                <?php echo $summary['total_students']; ?>
                                            </div>
                                            <small>Students</small>
                                        </div>
                                        <div class="text-center">
                                            <div style="font-size: 1.5rem; font-weight: 600; color: <?php echo $summary['avg_attendance'] >= 75 ? '#28a745' : '#dc3545'; ?>">
                                                <?php echo number_format($summary['avg_attendance'], 1); ?>%
                                            </div>
                                            <small>Avg Attendance</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚡ Quick Actions</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="attendance.php" class="btn btn-primary">
                                📅 Mark Attendance
                            </a>
                            <a href="academics.php" class="btn btn-success">
                                📊 Add Academic Records
                            </a>
                            <a href="reports.php" class="btn btn-info">
                                📋 Generate Class Reports
                            </a>
                            <a href="dashboard.php" class="btn btn-outline">
                                🏠 Back to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div id="studentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Student Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="studentDetails">
                <!-- Student details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/sidebar.js"></script>

    <style>
    .filter-form {
        background: var(--light-color);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 0;
    }

    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        animation: fadeIn 0.3s;
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: var(--border-radius);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        background: var(--light-color);
    }

    .close {
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        color: var(--text-secondary);
    }

    .close:hover {
        color: var(--text-primary);
    }

    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
        
        table {
            font-size: 0.9rem;
        }
    }
    </style>

    <script>
    function viewStudent(studentId) {
        // Show modal
        document.getElementById('studentModal').style.display = 'block';
        document.getElementById('studentDetails').innerHTML = '<div class="text-center" style="padding: 2rem;"><div class="loading-spinner"></div><p>Loading student details...</p></div>';
        
        // Fetch student details via AJAX
        fetch('../includes/ajax_helpers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_student_details&student_id=' + studentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStudentDetails(data.student);
            } else {
                document.getElementById('studentDetails').innerHTML = 
                    '<div class="alert alert-danger" style="margin: 1rem;">Error loading student details: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('studentDetails').innerHTML = 
                '<div class="alert alert-danger" style="margin: 1rem;">Error: ' + error.message + '</div>';
        });
    }

    function displayStudentDetails(student) {
        const html = `
            <div style="padding: 1.5rem;">
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        ${student.photo ? 
                            `<img src="../uploads/photos/${student.photo}" alt="Student Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">` :
                            `<div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 600;">${student.first_name.charAt(0).toUpperCase()}</div>`
                        }
                    </div>
                    <div>
                        <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">${student.first_name} ${student.last_name}</h3>
                        <div><strong>Admission No:</strong> ${student.admission_no}</div>
                        <div><strong>Roll No:</strong> ${student.roll_no || 'Not assigned'}</div>
                        <div><strong>Class:</strong> ${student.class_name} ${student.section}</div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div><strong>Father's Name:</strong> ${student.father_name}</div>
                    <div><strong>Mother's Name:</strong> ${student.mother_name}</div>
                    <div><strong>Date of Birth:</strong> ${new Date(student.date_of_birth).toLocaleDateString()}</div>
                    <div><strong>Gender:</strong> ${student.gender.charAt(0).toUpperCase() + student.gender.slice(1)}</div>
                    <div><strong>Mobile:</strong> ${student.mobile_no || 'Not provided'}</div>
                    <div><strong>Parent Mobile:</strong> ${student.parent_mobile}</div>
                    <div><strong>Village:</strong> ${student.village}</div>
                    <div><strong>Academic Year:</strong> ${student.academic_year}</div>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <a href="attendance.php?class_id=${student.class_id}&date=${new Date().toISOString().split('T')[0]}" 
                       class="btn btn-primary">📅 Mark Attendance</a>
                    <a href="academics.php?student_id=${student.id}" class="btn btn-success">📊 Academic Records</a>
                </div>
            </div>
        `;
        
        document.getElementById('studentDetails').innerHTML = html;
    }

    function closeModal() {
        document.getElementById('studentModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('studentModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Loading spinner CSS
    const style = document.createElement('style');
    style.textContent = `
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid var(--primary-color);
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
