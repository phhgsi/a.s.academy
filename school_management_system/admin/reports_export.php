<?php
require_once '../config/database.php';
require_once '../export/Exporter.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Validate format parameter
$format = $_GET['format'] ?? '';
if (!in_array($format, ['csv', 'pdf'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format. Use csv or pdf']);
    exit();
}

// Validate report type
$reportType = $_GET['report_type'] ?? '';
$allowedReports = ['students', 'fees', 'expenses', 'attendance', 'academic', 'financial', 'class_wise', 'village_wise'];
if (!in_array($reportType, $allowedReports)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid report type']);
    exit();
}

try {
    $reportData = [];
    $reportTitle = '';
    $headers = [];
    
    // Build date filters
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-t');
    
    switch ($reportType) {
        case 'students':
            $reportTitle = 'Students Report';
            $whereConditions = ['s.is_active = 1'];
            $params = [];
            
            if (!empty($_GET['class_filter'])) {
                $whereConditions[] = 's.class_id = ?';
                $params[] = $_GET['class_filter'];
            }
            
            if (!empty($_GET['village_filter'])) {
                $whereConditions[] = 's.village = ?';
                $params[] = $_GET['village_filter'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $stmt = $pdo->prepare("
                SELECT 
                    s.admission_no,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.father_name,
                    s.mother_name,
                    s.date_of_birth,
                    s.gender,
                    s.mobile_no,
                    s.parent_mobile,
                    s.address,
                    s.village,
                    c.class_name,
                    c.section,
                    s.academic_year,
                    s.admission_date
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE $whereClause
                ORDER BY c.class_name, s.admission_no
            ");
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Admission No', 'Student Name', 'Father Name', 'Mother Name', 'DOB', 'Gender', 'Mobile', 'Parent Mobile', 'Address', 'Village', 'Class', 'Section', 'Academic Year', 'Admission Date'];
            break;
            
        case 'fees':
            $reportTitle = 'Fee Collection Report';
            $stmt = $pdo->prepare("
                SELECT 
                    fp.receipt_no,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.admission_no,
                    c.class_name,
                    fp.amount,
                    fp.payment_method,
                    fp.fee_type,
                    fp.payment_date,
                    fp.academic_year,
                    u.full_name as collected_by_name
                FROM fee_payments fp 
                JOIN students s ON fp.student_id = s.id 
                LEFT JOIN classes c ON s.class_id = c.id 
                LEFT JOIN users u ON fp.collected_by = u.id
                WHERE fp.payment_date BETWEEN ? AND ?
                ORDER BY fp.payment_date DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Receipt No', 'Student Name', 'Admission No', 'Class', 'Amount', 'Payment Method', 'Fee Type', 'Payment Date', 'Academic Year', 'Collected By'];
            break;
            
        case 'attendance':
            $reportTitle = 'Attendance Report';
            $whereConditions = ['a.attendance_date BETWEEN ? AND ?'];
            $params = [$dateFrom, $dateTo];
            
            if (!empty($_GET['class_filter'])) {
                $whereConditions[] = 'a.class_id = ?';
                $params[] = $_GET['class_filter'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $stmt = $pdo->prepare("
                SELECT 
                    a.attendance_date,
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    s.admission_no,
                    c.class_name,
                    c.section,
                    a.status,
                    a.remarks,
                    u.full_name as marked_by_name
                FROM attendance a 
                JOIN students s ON a.student_id = s.id 
                LEFT JOIN classes c ON a.class_id = c.id 
                LEFT JOIN users u ON a.marked_by = u.id
                WHERE $whereClause
                ORDER BY a.attendance_date DESC, c.class_name, s.first_name
            ");
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Date', 'Student Name', 'Admission No', 'Class', 'Section', 'Status', 'Remarks', 'Marked By'];
            break;
            
        case 'class_wise':
            $reportTitle = 'Class-wise Summary Report';
            $stmt = $pdo->prepare("
                SELECT 
                    c.class_name,
                    c.section,
                    COUNT(s.id) as total_students,
                    COUNT(CASE WHEN s.gender = 'male' THEN 1 END) as male_students,
                    COUNT(CASE WHEN s.gender = 'female' THEN 1 END) as female_students,
                    COALESCE(SUM(fp.amount), 0) as total_fees_collected,
                    COALESCE(AVG(fp.amount), 0) as avg_fee_per_student
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id AND s.is_active = 1
                LEFT JOIN fee_payments fp ON s.id = fp.student_id 
                    AND fp.payment_date BETWEEN ? AND ?
                WHERE c.is_active = 1
                GROUP BY c.id, c.class_name, c.section
                ORDER BY c.class_name
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Class', 'Section', 'Total Students', 'Male Students', 'Female Students', 'Total Fees Collected', 'Avg Fee per Student'];
            break;
            
        case 'village_wise':
            $reportTitle = 'Village-wise Summary Report';
            $stmt = $pdo->prepare("
                SELECT 
                    s.village,
                    COUNT(s.id) as total_students,
                    COUNT(CASE WHEN s.gender = 'male' THEN 1 END) as male_students,
                    COUNT(CASE WHEN s.gender = 'female' THEN 1 END) as female_students,
                    COALESCE(SUM(fp.amount), 0) as total_fees_collected
                FROM students s
                LEFT JOIN fee_payments fp ON s.id = fp.student_id 
                    AND fp.payment_date BETWEEN ? AND ?
                WHERE s.is_active = 1 AND s.village IS NOT NULL
                GROUP BY s.village
                ORDER BY total_students DESC, s.village
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Village', 'Total Students', 'Male Students', 'Female Students', 'Total Fees Collected'];
            break;
            
        case 'financial':
            $reportTitle = 'Financial Summary Report';
            // Get fee collections
            $stmt = $pdo->prepare("
                SELECT 
                    'Fee Collection' as category,
                    fp.fee_type as subcategory,
                    COUNT(*) as count,
                    SUM(fp.amount) as total_amount,
                    AVG(fp.amount) as avg_amount
                FROM fee_payments fp
                WHERE fp.payment_date BETWEEN ? AND ?
                GROUP BY fp.fee_type
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $headers = ['Category', 'Type', 'Count', 'Total Amount', 'Average Amount'];
            break;
            
        default:
            throw new Exception('Unsupported report type');
    }
    
    // Check if any data found
    if (empty($reportData)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'No data found for the specified report criteria']);
        exit();
    }
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "{$reportType}_report_export_$timestamp";
    
    if ($format === 'csv') {
        // CSV Data rows
        $dataRows = [];
        foreach ($reportData as $row) {
            $csvRow = [];
            foreach ($row as $value) {
                if (is_numeric($value) && strpos($value, '.') !== false) {
                    $csvRow[] = number_format($value, 2);
                } elseif ($value && strtotime($value) !== false && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                    $csvRow[] = date('d/m/Y', strtotime($value));
                } else {
                    $csvRow[] = $value ?? '';
                }
            }
            $dataRows[] = $csvRow;
        }
        
        Exporter::csv($filename, $headers, $dataRows);
        
    } else {
        // PDF Export
        include_once '../includes/school_logo.php';
        
        // Generate HTML for PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            font-size: 12px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .school-info { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 15px;
        }
        .school-logo { 
            width: 60px; 
            height: 60px; 
        }
        .school-details h1 { 
            margin: 0; 
            color: #333; 
            font-size: 18px;
        }
        .school-details p { 
            margin: 5px 0; 
            color: #666; 
            font-size: 10px;
        }
        .report-title { 
            font-size: 16px; 
            font-weight: bold; 
            margin: 20px 0; 
            text-align: center;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left;
            font-size: 10px;
        }
        th { 
            background-color: #f5f5f5; 
            font-weight: bold;
        }
        .summary { 
            margin-top: 20px; 
            padding: 15px; 
            background-color: #f9f9f9; 
            border-left: 4px solid #007bff;
        }
        .footer { 
            position: fixed; 
            bottom: 20px; 
            left: 20px; 
            right: 20px; 
            text-align: center; 
            font-size: 8px; 
            color: #666; 
            border-top: 1px solid #ddd; 
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-info">
            ' . getSchoolLogo('md') . '
            <div class="school-details">
                <h1>' . htmlspecialchars(getSchoolName()) . '</h1>
                <p>' . htmlspecialchars($reportTitle) . '</p>
                <p>Period: ' . date('d/m/Y', strtotime($dateFrom)) . ' to ' . date('d/m/Y', strtotime($dateTo)) . '</p>
                <p>Generated on: ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </div>
    </div>
    
    <div class="report-title">' . htmlspecialchars($reportTitle) . '</div>
    
    <table>
        <thead>
            <tr>';
            
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        
        $html .= '</tr>
        </thead>
        <tbody>';
        
        foreach ($reportData as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $value) {
                if (is_numeric($value) && strpos($value, '.') !== false) {
                    $html .= '<td>₹' . number_format($value, 2) . '</td>';
                } elseif ($value && strtotime($value) !== false && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                    $html .= '<td>' . date('d/m/Y', strtotime($value)) . '</td>';
                } else {
                    $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
                }
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="summary">
        <strong>Report Summary:</strong><br>
        Total Records: ' . count($reportData) . '<br>
        Report Period: ' . date('d/m/Y', strtotime($dateFrom)) . ' to ' . date('d/m/Y', strtotime($dateTo)) . '
    </div>
    
    <div class="footer">
        <p>This is a system-generated report | ' . htmlspecialchars(getSchoolName()) . ' | Page {PAGENO} of {nb}</p>
    </div>
</body>
</html>';
        
        Exporter::pdf($filename, $html);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
    exit();
}
?>
