<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$students = [];

try {
    $where_conditions = ['s.is_active = 1'];
    $params = [];
    
    // Filter by class
    if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
        $where_conditions[] = 's.class_id = ?';
        $params[] = $_GET['class_id'];
    }
    
    // Filter by village
    if (isset($_GET['village']) && !empty($_GET['village'])) {
        $where_conditions[] = 's.village = ?';
        $params[] = $_GET['village'];
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT s.id, s.admission_no, s.first_name, s.last_name, s.village, c.class_name, c.section
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE $where_clause
        ORDER BY s.first_name, s.last_name
    ");
    
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

header('Content-Type: application/json');
echo json_encode($students);
?>
