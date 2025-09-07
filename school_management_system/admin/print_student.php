<?php
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher', 'cashier'])) {
    header('Location: ../login.php');
    exit();
}

$student_id = $_GET['id'] ?? '';
$print_type = $_GET['type'] ?? 'profile';

if (!$student_id) {
    header('Location: students.php');
    exit();
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section, c.academic_year 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit();
}

// Get additional data based on print type
switch ($print_type) {
    case 'fees':
        $stmt = $pdo->prepare("
            SELECT * FROM fee_payments 
            WHERE student_id = ? 
            ORDER BY payment_date DESC
        ");
        $stmt->execute([$student_id]);
        $fee_payments = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_paid 
            FROM fee_payments 
            WHERE student_id = ?
        ");
        $stmt->execute([$student_id]);
        $total_paid = $stmt->fetch()['total_paid'];
        break;
        
    case 'attendance':
        $stmt = $pdo->prepare("
            SELECT a.*, c.class_name, c.section
            FROM attendance a 
            LEFT JOIN classes c ON a.class_id = c.id
            WHERE a.student_id = ? 
            ORDER BY a.attendance_date DESC
            LIMIT 100
        ");
        $stmt->execute([$student_id]);
        $attendance_records = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
            FROM attendance 
            WHERE student_id = ?
        ");
        $stmt->execute([$student_id]);
        $attendance_summary = $stmt->fetch();
        break;
        
    case 'academic':
        $stmt = $pdo->prepare("
            SELECT ar.*, s.subject_name, s.subject_code
            FROM academic_records ar 
            JOIN subjects s ON ar.subject_id = s.id 
            WHERE ar.student_id = ? 
            ORDER BY ar.exam_date DESC
        ");
        $stmt->execute([$student_id]);
        $academic_records = $stmt->fetchAll();
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($print_type); ?> Report - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: #000;
        }
        
        .print-header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .print-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
            color: #000;
        }
        
        .print-header .school-address {
            margin: 10px 0 0 0;
            font-size: 14px;
            color: #666;
        }
        
        .print-header .report-title {
            margin: 15px 0 0 0;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
        }
        
        .student-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }
        
        .student-details {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 20px;
            align-items: start;
        }
        
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .student-photo {
            text-align: center;
        }
        
        .student-photo img {
            width: 150px;
            height: 180px;
            border: 2px solid #ddd;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .student-photo .placeholder {
            width: 150px;
            height: 180px;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            border-radius: 8px;
        }
        
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #333;
        }
        
        .info-value {
            flex: 1;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 12px 8px;
            text-align: left;
        }
        
        th {
            background: #f5f5f5;
            font-weight: bold;
            color: #000;
        }
        
        .summary-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .print-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .no-print {
            display: none !important;
        }
        
        @media screen {
            .print-controls {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
            }
            
            .print-controls button {
                margin-left: 10px;
                padding: 10px 20px;
                background: #3b82f6;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
            }
            
            .print-controls button:hover {
                background: #2563eb;
            }
        }
        
        @media print {
            .print-controls {
                display: none !important;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .student-details {
                grid-template-columns: 1fr;
            }
            
            .student-photo {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="window.print()">🖨️ Print</button>
        <button onclick="window.close()">❌ Close</button>
    </div>

    <?php 
    $report_title = ucfirst($print_type) . ' Report';
    $additional_info = [
        'Student' => $student['first_name'] . ' ' . $student['last_name'],
        'Admission No' => $student['admission_no'],
        'Class' => $student['class_name'] . ' - ' . $student['section']
    ];
    include '../includes/print_header.php'; 
    ?>

    <main class="print-body">

    <!-- Student Information Header -->
    <div class="student-header">
        <div class="student-details">
            <div class="student-info">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admission No:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['admission_no']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Class:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Academic Year:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['academic_year']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Father's Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['father_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mother's Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['mother_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($student['date_of_birth'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mobile:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['mobile_no'] ?: 'Not provided'); ?></span>
                </div>
            </div>
            
            <div class="student-photo">
                <?php if ($student['photo']): ?>
                    <img src="../uploads/photos/<?php echo $student['photo']; ?>" alt="Student Photo">
                <?php else: ?>
                    <div class="placeholder">
                        <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Print Type Specific Content -->
    <?php if ($print_type === 'fees'): ?>
        <div class="summary-section">
            <h3>Fee Payment Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">₹<?php echo number_format($total_paid, 2); ?></div>
                    <div class="summary-label">Total Paid</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo count($fee_payments); ?></div>
                    <div class="summary-label">Total Payments</div>
                </div>
            </div>
        </div>

        <h3>Fee Payment History</h3>
        <table>
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
                <?php if (empty($fee_payments)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No fee payments found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fee_payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                            <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($print_type === 'attendance'): ?>
        <div class="summary-section">
            <h3>Attendance Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value"><?php echo $attendance_summary['attendance_percentage'] ?? '0'; ?>%</div>
                    <div class="summary-label">Attendance Rate</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $attendance_summary['present_days'] ?? '0'; ?></div>
                    <div class="summary-label">Present Days</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $attendance_summary['total_days'] ?? '0'; ?></div>
                    <div class="summary-label">Total Days</div>
                </div>
            </div>
        </div>

        <h3>Attendance Records</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendance_records)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No attendance records found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($record['attendance_date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['class_name'] . ' - ' . $record['section']); ?></td>
                            <td style="color: <?php echo $record['status'] === 'present' ? 'green' : ($record['status'] === 'late' ? 'orange' : 'red'); ?>;">
                                <?php echo ucfirst($record['status']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($print_type === 'academic'): ?>
        <h3>Academic Records</h3>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Subject Code</th>
                    <th>Exam Type</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                    <th>Exam Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($academic_records)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No academic records found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($academic_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($record['exam_type']); ?></td>
                            <td><?php echo $record['marks_obtained']; ?></td>
                            <td><?php echo $record['total_marks']; ?></td>
                            <td><?php echo round(($record['marks_obtained'] / $record['total_marks']) * 100, 2); ?>%</td>
                            <td style="color: <?php echo ($record['grade'] === 'A' || $record['grade'] === 'A+') ? 'green' : (($record['grade'] === 'B') ? 'orange' : 'red'); ?>;">
                                <?php echo $record['grade']; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($record['exam_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: // profile ?>
        <h3>Complete Student Profile</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 20px 0;">
            <div>
                <h4>Personal Information</h4>
                <div class="info-row">
                    <span class="info-label">Gender:</span>
                    <span class="info-value"><?php echo ucfirst($student['gender']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Religion:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['religion'] ?: 'Not specified'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['category'] ?: 'Not specified'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Blood Group:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['blood_group'] ?: 'Not specified'); ?></span>
                </div>
            </div>
            
            <div>
                <h4>Contact Information</h4>
                <div class="info-row">
                    <span class="info-label">Village:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['village']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Post Office:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['post_office'] ?: 'Not specified'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tehsil:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['tehsil'] ?: 'Not specified'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">District:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['district'] ?: 'Not specified'); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    </main>

    <div class="print-footer-component">
        <p class="generation-info">
            <strong>Generated on:</strong> <?php echo date('d/m/Y H:i:s'); ?> | 
            <strong>Generated by:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)
        </p>
        <p class="copyright">© <?php echo date('Y'); ?> <?php echo getSchoolName(); ?>. All rights reserved.</p>
    </div>

    <script>
        // Auto-print when page loads (optional)
        window.addEventListener('load', function() {
            // Uncomment the line below to auto-print
            // window.print();
        });
    </script>
</body>
</html>
