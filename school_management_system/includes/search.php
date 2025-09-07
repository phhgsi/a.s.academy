<?php
require_once dirname(__DIR__) . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 10);

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

$results = [];
$searchTerm = '%' . $query . '%';

try {
    // Search Students
    $stmt = $pdo->prepare("
        SELECT 'student' as type, id, CONCAT(first_name, ' ', last_name) as name, 
               admission_no as identifier, class_id
        FROM students 
        WHERE (first_name LIKE ? OR last_name LIKE ? OR admission_no LIKE ?) 
              AND is_active = 1
        ORDER BY first_name, last_name
        LIMIT ?
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
    $students = $stmt->fetchAll();

    foreach ($students as $student) {
        // Get class name
        $classStmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $classStmt->execute([$student['class_id']]);
        $class = $classStmt->fetch();
        
        $results[] = [
            'type' => 'student',
            'id' => $student['id'],
            'title' => $student['name'],
            'subtitle' => 'Student • ' . ($class['class_name'] ?? 'No Class') . ' • ' . $student['identifier'],
            'url' => 'students.php?search=' . urlencode($student['identifier']),
            'icon' => 'person'
        ];
    }

    // Search Teachers (only for admin)
    if ($_SESSION['user_role'] === 'admin') {
        $stmt = $pdo->prepare("
            SELECT 'teacher' as type, id, name, email
            FROM users 
            WHERE role = 'teacher' AND (name LIKE ? OR email LIKE ?) AND is_active = 1
            ORDER BY name
            LIMIT ?
        ");
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        $teachers = $stmt->fetchAll();

        foreach ($teachers as $teacher) {
            $results[] = [
                'type' => 'teacher',
                'id' => $teacher['id'],
                'title' => $teacher['name'],
                'subtitle' => 'Teacher • ' . $teacher['email'],
                'url' => 'teachers.php?search=' . urlencode($teacher['email']),
                'icon' => 'person-badge'
            ];
        }

        // Search Classes
        $stmt = $pdo->prepare("
            SELECT 'class' as type, id, class_name, section
            FROM classes 
            WHERE (class_name LIKE ? OR section LIKE ?) AND is_active = 1
            ORDER BY class_name, section
            LIMIT ?
        ");
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        $classes = $stmt->fetchAll();

        foreach ($classes as $class) {
            $results[] = [
                'type' => 'class',
                'id' => $class['id'],
                'title' => $class['class_name'] . ' - ' . $class['section'],
                'subtitle' => 'Class',
                'url' => 'classes.php?id=' . $class['id'],
                'icon' => 'building'
            ];
        }
    }

    // Limit total results
    $results = array_slice($results, 0, $limit);

    echo json_encode([
        'results' => $results,
        'total' => count($results),
        'query' => $query
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>
