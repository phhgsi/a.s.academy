<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'get_students') {
    $class_id = $_POST['class_id'] ?? '';
    $village = $_POST['village'] ?? '';
    
    // Build query based on filters
    $sql = "SELECT id, first_name, last_name, admission_no, roll_no FROM students WHERE is_active = 1";
    $params = [];
    
    if (!empty($class_id)) {
        $sql .= " AND class_id = ?";
        $params[] = $class_id;
    }
    
    if (!empty($village)) {
        $sql .= " AND village LIKE ?";
        $params[] = "%$village%";
    }
    
    $sql .= " ORDER BY first_name, last_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'students' => $students]);

} elseif ($action === 'get_student_details') {
    $student_id = $_POST['student_id'] ?? '';
    
    if (empty($student_id)) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit;
    }
    
    // Get student details
    $stmt = $pdo->prepare("
        SELECT s.*, c.class_name, c.section
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE s.id = ? AND s.is_active = 1
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'student' => $student]);

} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
