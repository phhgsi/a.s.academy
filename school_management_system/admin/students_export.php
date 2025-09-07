<?php
require_once '../config/database.php';
require_once '../export/Exporter.php';
require_once '../includes/academic_year.php';

// Check if user is authorized
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher', 'cashier'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get export parameters
$format = $_GET['format'] ?? 'csv';
$class_filter = $_GET['class_id'] ?? '';
$academic_year_filter = $_GET['academic_year'] ?? getCurrentAcademicYear();

// Validate format
if (!Exporter::validateExportParams($format, ['dummy'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid export format']);
    exit();
}

try {
    // Build query with filters
    $where_conditions = ['s.is_active = 1'];
    $params = [];
    
    if ($academic_year_filter) {
        $where_conditions[] = 's.academic_year = ?';
        $params[] = $academic_year_filter;
    }
    
    if ($class_filter) {
        $where_conditions[] = 's.class_id = ?';
        $params[] = $class_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Fetch students data
    $stmt = $pdo->prepare("
        SELECT 
            s.admission_no,
            CONCAT(s.first_name, ' ', s.last_name) as full_name,
            s.father_name,
            s.mother_name,
            s.date_of_birth,
            s.gender,
            s.blood_group,
            s.category,
            s.religion,
            s.mobile_no,
            s.parent_mobile,
            s.email,
            s.address,
            s.village,
            s.pincode,
            s.aadhar_no,
            s.samagra_id,
            c.class_name,
            c.section,
            s.academic_year,
            s.admission_date
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE $where_clause
        ORDER BY s.admission_no
    ");
    
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    if (empty($students)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No students found for export']);
        exit();
    }
    
    // Prepare headers
    $headers = [
        'Admission No',
        'Student Name',
        'Father Name',
        'Mother Name',
        'Date of Birth',
        'Gender',
        'Blood Group',
        'Category',
        'Religion',
        'Mobile No',
        'Parent Mobile',
        'Email',
        'Address',
        'Village',
        'PIN Code',
        'Aadhar No',
        'Samagra ID',
        'Class',
        'Section',
        'Academic Year',
        'Admission Date'
    ];
    
    // Prepare data rows
    $data_rows = [];
    foreach ($students as $student) {
        $data_rows[] = [
            $student['admission_no'],
            $student['full_name'],
            $student['father_name'],
            $student['mother_name'],
            date('d/m/Y', strtotime($student['date_of_birth'])),
            ucfirst($student['gender']),
            $student['blood_group'] ?: 'N/A',
            $student['category'] ?: 'N/A',
            $student['religion'] ?: 'N/A',
            $student['mobile_no'] ?: 'N/A',
            $student['parent_mobile'] ?: 'N/A',
            $student['email'] ?: 'N/A',
            $student['address'],
            $student['village'],
            $student['pincode'] ?: 'N/A',
            $student['aadhar_no'] ?: 'N/A',
            $student['samagra_id'] ?: 'N/A',
            $student['class_name'] ?: 'N/A',
            $student['section'] ?: 'N/A',
            $student['academic_year'],
            date('d/m/Y', strtotime($student['admission_date']))
        ];
    }
    
    // Generate filename
    $filename_parts = ['students'];
    if ($class_filter) {
        $class_stmt = $pdo->prepare("SELECT class_name, section FROM classes WHERE id = ?");
        $class_stmt->execute([$class_filter]);
        $class_info = $class_stmt->fetch();
        if ($class_info) {
            $filename_parts[] = Exporter::sanitizeFilename($class_info['class_name'] . '_' . $class_info['section']);
        }
    }
    $filename_parts[] = $academic_year_filter;
    $filename = implode('_', $filename_parts);
    $filename = Exporter::sanitizeFilename($filename);
    
    // Export based on format
    if ($format === 'csv') {
        $title = 'Students List - ' . $academic_year_filter;
        if ($class_filter && isset($class_info)) {
            $title .= ' - ' . $class_info['class_name'] . ' ' . $class_info['section'];
        }
        
        Exporter::csv($filename, $headers, $data_rows, $title);
        
    } elseif ($format === 'pdf') {
        // Generate HTML table
        $table_html = Exporter::generateHtmlTable($headers, $data_rows);
        
        // Add summary information
        $summary_info = '<div class="summary-section">
            <h3>Export Summary</h3>
            <p><strong>Total Students:</strong> ' . count($students) . '</p>
            <p><strong>Academic Year:</strong> ' . htmlspecialchars($academic_year_filter) . '</p>';
        
        if ($class_filter && isset($class_info)) {
            $summary_info .= '<p><strong>Class:</strong> ' . htmlspecialchars($class_info['class_name'] . ' - ' . $class_info['section']) . '</p>';
        }
        
        $summary_info .= '</div>';
        
        $content = $summary_info . $table_html;
        
        $report_title = 'Students List';
        if ($class_filter && isset($class_info)) {
            $report_title .= ' - ' . $class_info['class_name'] . ' ' . $class_info['section'];
        }
        
        $html_document = Exporter::createHtmlDocument(
            'Students Export - ' . $academic_year_filter,
            $content,
            $report_title
        );
        
        Exporter::pdf($filename, $html_document);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
    exit();
}
?>
