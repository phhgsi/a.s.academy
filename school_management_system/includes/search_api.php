<?php
/**
 * Enhanced Search API
 * 
 * Provides comprehensive search functionality across:
 * - Students (by name, admission number, class, etc.)
 * - Teachers (by name, subject, department)
 * - Fee records and transactions
 * - Attendance records
 * - Academic records and results
 * - Events and activities
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

header('Content-Type: application/json');

class EnhancedSearch {
    
    private $pdo;
    private $user_role;
    private $user_id;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->user_role = $_SESSION['user_role'];
        $this->user_id = $_SESSION['user_id'];
    }
    
    /**
     * Main search function that routes to specific search types
     */
    public function search($query, $type = 'all', $filters = []) {
        $query = trim($query);
        
        if (strlen($query) < 2) {
            return ['results' => [], 'total' => 0, 'message' => 'Search query too short'];
        }
        
        // Sanitize search query
        $safe_query = SecurityManager::validateInput($query, 'text', ['max_length' => 100]);
        if (!$safe_query) {
            return ['results' => [], 'total' => 0, 'error' => 'Invalid search query'];
        }
        
        $results = [];
        
        switch ($type) {
            case 'students':
                $results = $this->searchStudents($safe_query, $filters);
                break;
            case 'teachers':
                $results = $this->searchTeachers($safe_query, $filters);
                break;
            case 'fees':
                $results = $this->searchFeeRecords($safe_query, $filters);
                break;
            case 'attendance':
                $results = $this->searchAttendance($safe_query, $filters);
                break;
            case 'events':
                $results = $this->searchEvents($safe_query, $filters);
                break;
            case 'all':
            default:
                $results = $this->searchAll($safe_query, $filters);
                break;
        }
        
        // Log search activity
        if (function_exists('log_activity')) {
            log_activity('search_performed', null, null, null, [
                'query' => $safe_query,
                'type' => $type,
                'results_count' => count($results['results'] ?? [])
            ]);
        }
        
        return $results;
    }
    
    /**
     * Search students by various criteria
     */
    private function searchStudents($query, $filters = []) {
        $where_conditions = ["s.is_active = 1"];
        $params = [];
        
        // Main search query
        $where_conditions[] = "(
            s.first_name LIKE CONCAT('%', ?, '%') OR 
            s.last_name LIKE CONCAT('%', ?, '%') OR 
            s.admission_no LIKE CONCAT('%', ?, '%') OR 
            s.roll_no LIKE CONCAT('%', ?, '%') OR
            s.father_name LIKE CONCAT('%', ?, '%') OR
            s.mother_name LIKE CONCAT('%', ?, '%') OR
            s.mobile_no LIKE CONCAT('%', ?, '%') OR
            s.parent_mobile LIKE CONCAT('%', ?, '%')
        )";
        $params = array_fill(0, 8, $query);
        
        // Apply filters
        if (!empty($filters['class_id'])) {
            $where_conditions[] = "s.class_id = ?";
            $params[] = $filters['class_id'];
        }
        
        if (!empty($filters['academic_year'])) {
            $where_conditions[] = "s.academic_year = ?";
            $params[] = $filters['academic_year'];
        }
        
        if (!empty($filters['gender'])) {
            $where_conditions[] = "s.gender = ?";
            $params[] = $filters['gender'];
        }
        
        $sql = "
            SELECT 
                s.*,
                c.class_name,
                c.section,
                u.email as user_email,
                CONCAT(s.first_name, ' ', s.last_name) as full_name,
                'student' as result_type
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY s.first_name, s.last_name
            LIMIT 50
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
        
        // Format results
        $formatted_results = [];
        foreach ($students as $student) {
            $formatted_results[] = [
                'id' => $student['id'],
                'type' => 'student',
                'title' => $student['full_name'],
                'subtitle' => "Admission: {$student['admission_no']} • Class: {$student['class_name']} {$student['section']}",
                'meta' => [
                    'admission_no' => $student['admission_no'],
                    'class' => "{$student['class_name']} {$student['section']}",
                    'father_name' => $student['father_name'],
                    'mobile' => $student['mobile_no'],
                    'academic_year' => $student['academic_year']
                ],
                'url' => "/admin/students.php?action=view&id={$student['id']}",
                'image' => $student['photo'] ? "/uploads/students/{$student['photo']}" : null
            ];
        }
        
        return [
            'results' => $formatted_results,
            'total' => count($formatted_results),
            'type' => 'students'
        ];
    }
    
    /**
     * Search teachers and staff
     */
    private function searchTeachers($query, $filters = []) {
        $where_conditions = ["u.role IN ('teacher', 'admin') AND u.is_active = 1"];
        $params = [];
        
        // Main search query
        $where_conditions[] = "(
            u.full_name LIKE CONCAT('%', ?, '%') OR 
            u.username LIKE CONCAT('%', ?, '%') OR 
            u.email LIKE CONCAT('%', ?, '%')
        )";
        $params = array_fill(0, 3, $query);
        
        $sql = "
            SELECT 
                u.*,
                GROUP_CONCAT(DISTINCT CONCAT(c.class_name, ' ', c.section) SEPARATOR ', ') as classes_taught,
                GROUP_CONCAT(DISTINCT sub.subject_name SEPARATOR ', ') as subjects_taught,
                'teacher' as result_type
            FROM users u
            LEFT JOIN classes c ON c.class_teacher_id = u.id
            LEFT JOIN subjects sub ON sub.teacher_id = u.id
            WHERE " . implode(' AND ', $where_conditions) . "
            GROUP BY u.id
            ORDER BY u.full_name
            LIMIT 50
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll();
        
        // Format results
        $formatted_results = [];
        foreach ($teachers as $teacher) {
            $formatted_results[] = [
                'id' => $teacher['id'],
                'type' => 'teacher',
                'title' => $teacher['full_name'],
                'subtitle' => ucfirst($teacher['role']) . " • " . ($teacher['subjects_taught'] ?: 'No subjects assigned'),
                'meta' => [
                    'username' => $teacher['username'],
                    'email' => $teacher['email'],
                    'role' => $teacher['role'],
                    'classes' => $teacher['classes_taught'],
                    'subjects' => $teacher['subjects_taught']
                ],
                'url' => "/admin/teachers.php?action=view&id={$teacher['id']}",
                'image' => null
            ];
        }
        
        return [
            'results' => $formatted_results,
            'total' => count($formatted_results),
            'type' => 'teachers'
        ];
    }
    
    /**
     * Search fee records and transactions
     */
    private function searchFeeRecords($query, $filters = []) {
        // Only allow admin and cashier to search fees
        if (!in_array($this->user_role, ['admin', 'cashier'])) {
            return ['results' => [], 'total' => 0, 'error' => 'Access denied'];
        }
        
        $where_conditions = [];
        $params = [];
        
        // Main search query
        $where_conditions[] = "(
            fp.receipt_no LIKE CONCAT('%', ?, '%') OR 
            CONCAT(s.first_name, ' ', s.last_name) LIKE CONCAT('%', ?, '%') OR
            s.admission_no LIKE CONCAT('%', ?, '%') OR
            fp.fee_type LIKE CONCAT('%', ?, '%')
        )";
        $params = array_fill(0, 4, $query);
        
        // Apply filters
        if (!empty($filters['payment_mode'])) {
            $where_conditions[] = "fp.payment_mode = ?";
            $params[] = $filters['payment_mode'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "fp.payment_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "fp.payment_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql = "
            SELECT 
                fp.*,
                s.first_name,
                s.last_name,
                s.admission_no,
                c.class_name,
                c.section,
                u.full_name as collected_by_name,
                'fee_payment' as result_type
            FROM fee_payments fp
            JOIN students s ON fp.student_id = s.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN users u ON fp.collected_by = u.id
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY fp.payment_date DESC, fp.created_at DESC
            LIMIT 50
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();
        
        // Format results
        $formatted_results = [];
        foreach ($payments as $payment) {
            $formatted_results[] = [
                'id' => $payment['id'],
                'type' => 'fee_payment',
                'title' => "Receipt #{$payment['receipt_no']}",
                'subtitle' => "{$payment['first_name']} {$payment['last_name']} • ₹" . number_format($payment['amount'], 2),
                'meta' => [
                    'student_name' => "{$payment['first_name']} {$payment['last_name']}",
                    'admission_no' => $payment['admission_no'],
                    'amount' => $payment['amount'],
                    'payment_date' => $payment['payment_date'],
                    'payment_mode' => $payment['payment_mode'],
                    'fee_type' => $payment['fee_type'],
                    'collected_by' => $payment['collected_by_name']
                ],
                'url' => "/admin/fees.php?action=view&id={$payment['id']}",
                'image' => null
            ];
        }
        
        return [
            'results' => $formatted_results,
            'total' => count($formatted_results),
            'type' => 'fee_payments'
        ];
    }
    
    /**
     * Search attendance records
     */
    private function searchAttendance($query, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        // Main search query
        $where_conditions[] = "(
            CONCAT(s.first_name, ' ', s.last_name) LIKE CONCAT('%', ?, '%') OR
            s.admission_no LIKE CONCAT('%', ?, '%') OR
            c.class_name LIKE CONCAT('%', ?, '%')
        )";
        $params = array_fill(0, 3, $query);
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "a.attendance_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "a.attendance_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['class_id'])) {
            $where_conditions[] = "a.class_id = ?";
            $params[] = $filters['class_id'];
        }
        
        $sql = "
            SELECT 
                a.*,
                s.first_name,
                s.last_name,
                s.admission_no,
                c.class_name,
                c.section,
                u.full_name as marked_by_name,
                'attendance' as result_type
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            LEFT JOIN classes c ON a.class_id = c.id
            LEFT JOIN users u ON a.marked_by = u.id
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY a.attendance_date DESC, s.first_name
            LIMIT 50
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $attendance = $stmt->fetchAll();
        
        // Format results
        $formatted_results = [];
        foreach ($attendance as $record) {
            $status_color = match($record['status']) {
                'present' => '#10b981',
                'absent' => '#ef4444',
                'late' => '#f59e0b',
                default => '#6b7280'
            };
            
            $formatted_results[] = [
                'id' => $record['id'],
                'type' => 'attendance',
                'title' => "{$record['first_name']} {$record['last_name']}",
                'subtitle' => "Attendance on " . date('d/m/Y', strtotime($record['attendance_date'])) . " • " . ucfirst($record['status']),
                'meta' => [
                    'student_name' => "{$record['first_name']} {$record['last_name']}",
                    'admission_no' => $record['admission_no'],
                    'class' => "{$record['class_name']} {$record['section']}",
                    'date' => $record['attendance_date'],
                    'status' => $record['status'],
                    'marked_by' => $record['marked_by_name']
                ],
                'url' => "/admin/attendance.php?date={$record['attendance_date']}&class_id={$record['class_id']}",
                'status_color' => $status_color,
                'image' => null
            ];
        }
        
        return [
            'results' => $formatted_results,
            'total' => count($formatted_results),
            'type' => 'attendance'
        ];
    }
    
    /**
     * Search events and calendar items
     */
    private function searchEvents($query, $filters = []) {
        $where_conditions = [];
        $params = [];
        
        // Main search query
        $where_conditions[] = "(
            e.title LIKE CONCAT('%', ?, '%') OR 
            e.description LIKE CONCAT('%', ?, '%') OR
            e.location LIKE CONCAT('%', ?, '%')
        )";
        $params = array_fill(0, 3, $query);
        
        // Apply filters
        if (!empty($filters['event_type'])) {
            $where_conditions[] = "e.event_type = ?";
            $params[] = $filters['event_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "e.event_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "e.event_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql = "
            SELECT 
                e.*,
                c.class_name,
                c.section,
                u.full_name as created_by_name,
                'event' as result_type
            FROM events e
            LEFT JOIN classes c ON e.class_id = c.id
            LEFT JOIN users u ON e.created_by = u.id
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY e.event_date DESC
            LIMIT 50
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        // Format results
        $formatted_results = [];
        foreach ($events as $event) {
            $formatted_results[] = [
                'id' => $event['id'],
                'type' => 'event',
                'title' => $event['title'],
                'subtitle' => date('d/m/Y', strtotime($event['event_date'])) . " • " . ucfirst($event['event_type']),
                'meta' => [
                    'description' => $event['description'],
                    'event_date' => $event['event_date'],
                    'start_time' => $event['start_time'],
                    'end_time' => $event['end_time'],
                    'location' => $event['location'],
                    'event_type' => $event['event_type'],
                    'target_audience' => $event['target_audience']
                ],
                'url' => "/admin/events.php?action=view&id={$event['id']}",
                'image' => null
            ];
        }
        
        return [
            'results' => $formatted_results,
            'total' => count($formatted_results),
            'type' => 'events'
        ];
    }
    
    /**
     * Combined search across all entities
     */
    private function searchAll($query, $filters = []) {
        $all_results = [];
        
        // Search students (limit to 10 for combined search)
        $student_results = $this->searchStudents($query, $filters);
        $all_results = array_merge($all_results, array_slice($student_results['results'], 0, 10));
        
        // Search teachers (limit to 5 for combined search)
        $teacher_results = $this->searchTeachers($query, $filters);
        $all_results = array_merge($all_results, array_slice($teacher_results['results'], 0, 5));
        
        // Search fee records (limit to 10 for combined search)
        if (in_array($this->user_role, ['admin', 'cashier'])) {
            $fee_results = $this->searchFeeRecords($query, $filters);
            $all_results = array_merge($all_results, array_slice($fee_results['results'], 0, 10));
        }
        
        // Search events (limit to 5 for combined search)
        $event_results = $this->searchEvents($query, $filters);
        $all_results = array_merge($all_results, array_slice($event_results['results'], 0, 5));
        
        return [
            'results' => $all_results,
            'total' => count($all_results),
            'type' => 'all',
            'breakdown' => [
                'students' => count($student_results['results']),
                'teachers' => count($teacher_results['results']),
                'fees' => count($fee_results['results'] ?? []),
                'events' => count($event_results['results'])
            ]
        ];
    }
    
    /**
     * Get search suggestions based on partial query
     */
    public function getSuggestions($query, $type = 'all') {
        $query = trim($query);
        
        if (strlen($query) < 2) {
            return ['suggestions' => []];
        }
        
        $suggestions = [];
        
        switch ($type) {
            case 'students':
                $suggestions = $this->getStudentSuggestions($query);
                break;
            case 'teachers':
                $suggestions = $this->getTeacherSuggestions($query);
                break;
            default:
                $suggestions = array_merge(
                    $this->getStudentSuggestions($query),
                    $this->getTeacherSuggestions($query)
                );
                break;
        }
        
        return [
            'suggestions' => array_slice($suggestions, 0, 10)
        ];
    }
    
    private function getStudentSuggestions($query) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT
                CONCAT(s.first_name, ' ', s.last_name) as suggestion,
                s.admission_no,
                'student' as type
            FROM students s
            WHERE s.is_active = 1
            AND (
                s.first_name LIKE CONCAT(?, '%') OR 
                s.last_name LIKE CONCAT(?, '%') OR 
                s.admission_no LIKE CONCAT(?, '%')
            )
            ORDER BY s.first_name
            LIMIT 5
        ");
        
        $stmt->execute([$query, $query, $query]);
        return $stmt->fetchAll();
    }
    
    private function getTeacherSuggestions($query) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT
                u.full_name as suggestion,
                u.username,
                'teacher' as type
            FROM users u
            WHERE u.role IN ('teacher', 'admin') 
            AND u.is_active = 1
            AND (
                u.full_name LIKE CONCAT(?, '%') OR 
                u.username LIKE CONCAT(?, '%')
            )
            ORDER BY u.full_name
            LIMIT 5
        ");
        
        $stmt->execute([$query, $query]);
        return $stmt->fetchAll();
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = new EnhancedSearch($pdo);
    
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all';
    
    // Build filters from GET parameters
    $filters = [];
    $allowed_filters = ['class_id', 'academic_year', 'gender', 'payment_mode', 'date_from', 'date_to', 'status', 'event_type'];
    
    foreach ($allowed_filters as $filter) {
        if (!empty($_GET[$filter])) {
            $filters[$filter] = SecurityManager::validateInput($_GET[$filter], 'string');
        }
    }
    
    // Handle suggestions request
    if (isset($_GET['suggestions'])) {
        $result = $search->getSuggestions($query, $type);
    } else {
        $result = $search->search($query, $type, $filters);
    }
    
    echo json_encode($result);
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
