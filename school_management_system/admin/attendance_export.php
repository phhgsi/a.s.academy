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

try {
    // Build query with optional filters
    $whereConditions = [];
    $params = [];
    
    // Filter by class if provided
    if (!empty($_GET['class_id'])) {
        $whereConditions[] = "a.class_id = ?";
        $params[] = $_GET['class_id'];
    }
    
    // Filter by attendance status if provided
    if (!empty($_GET['status'])) {
        $whereConditions[] = "a.status = ?";
        $params[] = $_GET['status'];
    }
    
    // Filter by date range if provided
    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "a.attendance_date >= ?";
        $params[] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "a.attendance_date <= ?";
        $params[] = $_GET['date_to'];
    }
    
    // Filter by academic year if provided  
    if (!empty($_GET['academic_year'])) {
        $whereConditions[] = "s.academic_year = ?";
        $params[] = $_GET['academic_year'];
    }
    
    // Build the WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Fetch attendance data
    $sql = "
        SELECT 
            a.attendance_date,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.admission_no,
            c.class_name,
            c.section,
            a.status,
            a.remarks,
            u.full_name as marked_by_name,
            a.created_at
        FROM attendance a 
        JOIN students s ON a.student_id = s.id 
        LEFT JOIN classes c ON a.class_id = c.id 
        LEFT JOIN users u ON a.marked_by = u.id
        $whereClause
        ORDER BY a.attendance_date DESC, c.class_name, s.first_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if any data found
    if (empty($attendance_records)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'No attendance data found for the specified criteria']);
        exit();
    }
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "attendance_export_$timestamp";
    
    if ($format === 'csv') {
        // CSV Headers
        $headers = [
            'Date',
            'Student Name', 
            'Admission No',
            'Class',
            'Section',
            'Status',
            'Remarks',
            'Marked By',
            'Entry Time'
        ];
        
        // CSV Data rows
        $dataRows = [];
        foreach ($attendance_records as $record) {
            $dataRows[] = [
                date('d/m/Y', strtotime($record['attendance_date'])),
                $record['student_name'],
                $record['admission_no'],
                $record['class_name'] ?? '',
                $record['section'] ?? '',
                ucfirst($record['status']),
                $record['remarks'] ?? '',
                $record['marked_by_name'] ?? '',
                date('d/m/Y H:i', strtotime($record['created_at']))
            ];
        }
        
        Exporter::csv($filename, $headers, $dataRows);
        
    } else {
        // PDF Export
        include_once '../includes/school_logo.php';
        
        // Calculate attendance statistics
        $totalRecords = count($attendance_records);
        $presentCount = count(array_filter($attendance_records, function($r) { return $r['status'] === 'present'; }));
        $absentCount = count(array_filter($attendance_records, function($r) { return $r['status'] === 'absent'; }));
        $lateCount = count(array_filter($attendance_records, function($r) { return $r['status'] === 'late'; }));
        
        $presentPercent = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
        $absentPercent = $totalRecords > 0 ? round(($absentCount / $totalRecords) * 100, 1) : 0;
        
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
            padding: 6px; 
            text-align: left;
            font-size: 9px;
        }
        th { 
            background-color: #f5f5f5; 
            font-weight: bold;
        }
        .status-present { color: #10b981; font-weight: bold; }
        .status-absent { color: #ef4444; font-weight: bold; }
        .status-late { color: #f59e0b; font-weight: bold; }
        .summary { 
            margin-top: 20px; 
            padding: 15px; 
            background-color: #f9f9f9; 
            border-left: 4px solid #007bff;
            font-size: 11px;
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
                <p>Attendance Records Report</p>
                <p>Generated on: ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </div>
    </div>
    
    <div class="report-title">Student Attendance Records</div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Student Name</th>
                <th>Admission No</th>
                <th>Class</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Marked By</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($attendance_records as $record) {
            $statusClass = 'status-' . $record['status'];
            $html .= '<tr>
                <td>' . date('d/m/Y', strtotime($record['attendance_date'])) . '</td>
                <td>' . htmlspecialchars($record['student_name']) . '</td>
                <td>' . htmlspecialchars($record['admission_no']) . '</td>
                <td>' . htmlspecialchars(($record['class_name'] ?? '') . ($record['section'] ? ' - ' . $record['section'] : '')) . '</td>
                <td class="' . $statusClass . '">' . ucfirst($record['status']) . '</td>
                <td>' . htmlspecialchars($record['remarks'] ?? '') . '</td>
                <td>' . htmlspecialchars($record['marked_by_name'] ?? '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="summary">
        <strong>Attendance Summary:</strong><br>
        Total Records: ' . $totalRecords . '<br>
        Present: ' . $presentCount . ' (' . $presentPercent . '%)<br>
        Absent: ' . $absentCount . ' (' . $absentPercent . '%)<br>
        Late: ' . $lateCount . ' records
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
