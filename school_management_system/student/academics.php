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
    // Get academic records
    $stmt = $pdo->prepare("
        SELECT ar.*, s.subject_name 
        FROM academic_records ar
        LEFT JOIN subjects s ON ar.subject_id = s.id
        WHERE ar.student_id = ?
        ORDER BY ar.academic_year DESC, ar.exam_type, s.subject_name
    ");
    $stmt->execute([$student['id']]);
    $academics = $stmt->fetchAll();
    
    // Group by academic year and exam type
    $academic_data = [];
    foreach ($academics as $record) {
        $year = $record['academic_year'];
        $exam = $record['exam_type'];
        if (!isset($academic_data[$year])) {
            $academic_data[$year] = [];
        }
        if (!isset($academic_data[$year][$exam])) {
            $academic_data[$year][$exam] = [];
        }
        $academic_data[$year][$exam][] = $record;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Records - Student Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">📚 My Academic Records</h1>
                    <p class="page-subtitle">View your grades and academic performance</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <!-- Student Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Student Information</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                            <div><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></div>
                            <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></div>
                            <div><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_no'] ?: 'Not assigned'); ?></div>
                        </div>
                    </div>

                    <?php if (empty($academic_data)): ?>
                        <div class="alert alert-info">
                            No academic records found. Your marks will appear here once they are entered by your teachers.
                        </div>
                    <?php else: ?>
                        <!-- Academic Records by Year -->
                        <?php foreach ($academic_data as $year => $exams): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Academic Year: <?php echo htmlspecialchars($year); ?></h3>
                                    <button class="btn btn-outline" onclick="printSection('year-<?php echo $year; ?>')">🖨️ Print</button>
                                </div>
                                
                                <div id="year-<?php echo $year; ?>">
                                    <?php foreach ($exams as $exam_type => $records): ?>
                                        <div class="mb-4">
                                            <h4 style="background: var(--light-color); padding: 1rem; margin-bottom: 1rem; border-radius: var(--border-radius); text-transform: capitalize;">
                                                <?php echo htmlspecialchars($exam_type); ?> Examination
                                            </h4>
                                            
                                            <div class="table-container">
                                                <table class="data-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Subject</th>
                                                            <th>Marks Obtained</th>
                                                            <th>Total Marks</th>
                                                            <th>Percentage</th>
                                                            <th>Grade</th>
                                                            <th>Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $total_obtained = 0;
                                                        $total_maximum = 0;
                                                        foreach ($records as $record): 
                                                            $percentage = $record['total_marks'] > 0 ? ($record['marks_obtained'] / $record['total_marks']) * 100 : 0;
                                                            $total_obtained += $record['marks_obtained'];
                                                            $total_maximum += $record['total_marks'];
                                                            
                                                            // Grade calculation
                                                            $grade = '';
                                                            if ($percentage >= 90) $grade = 'A+';
                                                            elseif ($percentage >= 80) $grade = 'A';
                                                            elseif ($percentage >= 70) $grade = 'B+';
                                                            elseif ($percentage >= 60) $grade = 'B';
                                                            elseif ($percentage >= 50) $grade = 'C';
                                                            elseif ($percentage >= 35) $grade = 'D';
                                                            else $grade = 'F';
                                                            
                                                            // Row color based on performance
                                                            $row_class = '';
                                                            if ($percentage < 35) $row_class = 'table-danger';
                                                            elseif ($percentage < 50) $row_class = 'table-warning';
                                                            elseif ($percentage >= 80) $row_class = 'table-success';
                                                        ?>
                                                            <tr class="<?php echo $row_class; ?>">
                                                                <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($record['marks_obtained']); ?></td>
                                                                <td><?php echo htmlspecialchars($record['total_marks']); ?></td>
                                                                <td><?php echo number_format($percentage, 2); ?>%</td>
                                                                <td><span class="grade-badge grade-<?php echo strtolower($grade); ?>"><?php echo $grade; ?></span></td>
                                                                <td><?php echo htmlspecialchars($record['remarks'] ?: '-'); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot style="background: var(--light-color); font-weight: 600;">
                                                        <tr>
                                                            <td><strong>Total</strong></td>
                                                            <td><strong><?php echo $total_obtained; ?></strong></td>
                                                            <td><strong><?php echo $total_maximum; ?></strong></td>
                                                            <td><strong><?php echo $total_maximum > 0 ? number_format(($total_obtained / $total_maximum) * 100, 2) : 0; ?>%</strong></td>
                                                            <td colspan="2">
                                                                <?php 
                                                                $overall_percentage = $total_maximum > 0 ? ($total_obtained / $total_maximum) * 100 : 0;
                                                                $overall_grade = '';
                                                                if ($overall_percentage >= 90) $overall_grade = 'A+';
                                                                elseif ($overall_percentage >= 80) $overall_grade = 'A';
                                                                elseif ($overall_percentage >= 70) $overall_grade = 'B+';
                                                                elseif ($overall_percentage >= 60) $overall_grade = 'B';
                                                                elseif ($overall_percentage >= 50) $overall_grade = 'C';
                                                                elseif ($overall_percentage >= 35) $overall_grade = 'D';
                                                                else $overall_grade = 'F';
                                                                ?>
                                                                <strong>Overall Grade: <span class="grade-badge grade-<?php echo strtolower($overall_grade); ?>"><?php echo $overall_grade; ?></span></strong>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Performance Summary -->
                    <?php if (!empty($academic_data)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">📊 Performance Summary</h3>
                            </div>
                            
                            <?php 
                            // Calculate overall statistics
                            $all_percentages = [];
                            $year_stats = [];
                            
                            foreach ($academic_data as $year => $exams) {
                                $year_total_obtained = 0;
                                $year_total_maximum = 0;
                                
                                foreach ($exams as $exam_type => $records) {
                                    foreach ($records as $record) {
                                        $year_total_obtained += $record['marks_obtained'];
                                        $year_total_maximum += $record['total_marks'];
                                    }
                                }
                                
                                if ($year_total_maximum > 0) {
                                    $year_percentage = ($year_total_obtained / $year_total_maximum) * 100;
                                    $year_stats[$year] = [
                                        'percentage' => $year_percentage,
                                        'obtained' => $year_total_obtained,
                                        'total' => $year_total_maximum
                                    ];
                                    $all_percentages[] = $year_percentage;
                                }
                            }
                            
                            $avg_percentage = !empty($all_percentages) ? array_sum($all_percentages) / count($all_percentages) : 0;
                            ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo number_format($avg_percentage, 2); ?>%</div>
                                    <div class="stat-label">Overall Average</div>
                                </div>
                                
                                <?php foreach ($year_stats as $year => $stats): ?>
                                    <div class="stat-card">
                                        <div class="stat-value"><?php echo number_format($stats['percentage'], 2); ?>%</div>
                                        <div class="stat-label"><?php echo $year; ?> Average</div>
                                        <small><?php echo $stats['obtained'] . '/' . $stats['total']; ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Grade Legend -->
                            <div style="margin-top: 2rem;">
                                <h4>Grade Scale:</h4>
                                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem;">
                                    <span class="grade-badge grade-a+">A+ (90-100%)</span>
                                    <span class="grade-badge grade-a">A (80-89%)</span>
                                    <span class="grade-badge grade-b+">B+ (70-79%)</span>
                                    <span class="grade-badge grade-b">B (60-69%)</span>
                                    <span class="grade-badge grade-c">C (50-59%)</span>
                                    <span class="grade-badge grade-d">D (35-49%)</span>
                                    <span class="grade-badge grade-f">F (Below 35%)</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <style>
    .grade-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .grade-a\+ { background-color: #28a745; }
    .grade-a { background-color: #20c997; }
    .grade-b\+ { background-color: #17a2b8; }
    .grade-b { background-color: #007bff; }
    .grade-c { background-color: #ffc107; color: #333; }
    .grade-d { background-color: #fd7e14; }
    .grade-f { background-color: #dc3545; }
    
    .table-success { background-color: rgba(40, 167, 69, 0.1); }
    .table-warning { background-color: rgba(255, 193, 7, 0.1); }
    .table-danger { background-color: rgba(220, 53, 69, 0.1); }
    
    .stat-card {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    .stat-label {
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
    }
    </style>
</body>
</html>
