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
    
    // Filter by payment method if provided
    if (!empty($_GET['payment_method'])) {
        $whereConditions[] = "fp.payment_method = ?";
        $params[] = $_GET['payment_method'];
    }
    
    // Filter by fee type if provided
    if (!empty($_GET['fee_type'])) {
        $whereConditions[] = "fp.fee_type = ?";
        $params[] = $_GET['fee_type'];
    }
    
    // Filter by date range if provided
    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "fp.payment_date >= ?";
        $params[] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "fp.payment_date <= ?";
        $params[] = $_GET['date_to'];
    }
    
    // Filter by academic year if provided
    if (!empty($_GET['academic_year'])) {
        $whereConditions[] = "fp.academic_year = ?";
        $params[] = $_GET['academic_year'];
    }
    
    // Build the WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Fetch fee payment data
    $sql = "
        SELECT 
            fp.receipt_no,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.admission_no,
            c.class_name,
            c.section,
            fp.amount,
            fp.payment_method,
            fp.fee_type,
            fp.payment_date,
            fp.academic_year,
            fp.remarks,
            u.full_name as collected_by_name,
            fp.created_at
        FROM fee_payments fp 
        JOIN students s ON fp.student_id = s.id 
        LEFT JOIN classes c ON s.class_id = c.id 
        LEFT JOIN users u ON fp.collected_by = u.id
        $whereClause
        ORDER BY fp.payment_date DESC, fp.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fee_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if any data found
    if (empty($fee_payments)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'No fee payment data found for the specified criteria']);
        exit();
    }
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "fee_payments_export_$timestamp";
    
    if ($format === 'csv') {
        // CSV Headers
        $headers = [
            'Receipt No',
            'Student Name', 
            'Admission No',
            'Class',
            'Section',
            'Amount (₹)',
            'Payment Method',
            'Fee Type',
            'Payment Date',
            'Academic Year',
            'Remarks',
            'Collected By',
            'Entry Date'
        ];
        
        // CSV Data rows
        $dataRows = [];
        foreach ($fee_payments as $payment) {
            $dataRows[] = [
                $payment['receipt_no'],
                $payment['student_name'],
                $payment['admission_no'],
                $payment['class_name'] ?? '',
                $payment['section'] ?? '',
                number_format($payment['amount'], 2),
                ucfirst($payment['payment_method']),
                $payment['fee_type'],
                date('d/m/Y', strtotime($payment['payment_date'])),
                $payment['academic_year'],
                $payment['remarks'] ?? '',
                $payment['collected_by_name'] ?? '',
                date('d/m/Y H:i', strtotime($payment['created_at']))
            ];
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
                <p>Fee Payment Records Report</p>
                <p>Generated on: ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </div>
    </div>
    
    <div class="report-title">Fee Payment Records</div>
    
    <table>
        <thead>
            <tr>
                <th>Receipt No</th>
                <th>Student Name</th>
                <th>Admission No</th>
                <th>Class</th>
                <th>Amount (₹)</th>
                <th>Payment Method</th>
                <th>Fee Type</th>
                <th>Payment Date</th>
                <th>Academic Year</th>
                <th>Collected By</th>
            </tr>
        </thead>
        <tbody>';
        
        $totalAmount = 0;
        foreach ($fee_payments as $payment) {
            $totalAmount += $payment['amount'];
            $html .= '<tr>
                <td>' . htmlspecialchars($payment['receipt_no']) . '</td>
                <td>' . htmlspecialchars($payment['student_name']) . '</td>
                <td>' . htmlspecialchars($payment['admission_no']) . '</td>
                <td>' . htmlspecialchars(($payment['class_name'] ?? '') . ($payment['section'] ? ' - ' . $payment['section'] : '')) . '</td>
                <td>₹' . number_format($payment['amount'], 2) . '</td>
                <td>' . ucfirst($payment['payment_method']) . '</td>
                <td>' . htmlspecialchars($payment['fee_type']) . '</td>
                <td>' . date('d/m/Y', strtotime($payment['payment_date'])) . '</td>
                <td>' . htmlspecialchars($payment['academic_year']) . '</td>
                <td>' . htmlspecialchars($payment['collected_by_name'] ?? '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="summary">
        <strong>Summary:</strong><br>
        Total Records: ' . count($fee_payments) . '<br>
        Total Amount Collected: ₹' . number_format($totalAmount, 2) . '
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
