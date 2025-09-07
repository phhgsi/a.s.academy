<?php
/**
 * Database Schema Test Script
 * Tests all major database queries to ensure they work with current schema
 */

require_once 'config/database.php';

echo "<h1>Database Schema Test Results</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .test-result { padding: 10px; margin: 10px 0; border-left: 4px solid; }
    .success { border-color: #4CAF50; background: #f0f8f0; }
    .error { border-color: #f44336; background: #fff0f0; }
    .warning { border-color: #ff9800; background: #fff8f0; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
</style>";

$test_queries = [
    'Students Count' => "SELECT COUNT(*) as count FROM students",
    'Teachers Count' => "SELECT COUNT(*) as count FROM users WHERE role = 'teacher'",
    'Classes List' => "SELECT id, class_name, section FROM classes LIMIT 5",
    'Fee Payments' => "SELECT COUNT(*) as count FROM fee_payments",
    'Events List' => "SELECT id, title, event_date FROM events LIMIT 5",
    'Academic Years' => "SELECT academic_year FROM academic_years LIMIT 5",
    'Attendance Records' => "SELECT COUNT(*) as count FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
    'Expenses Count' => "SELECT COUNT(*) as count FROM expenses",
];

$passed_tests = 0;
$total_tests = count($test_queries);

foreach ($test_queries as $test_name => $query) {
    echo "<div class='test-result ";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        echo "success'>";
        echo "<h3>✅ {$test_name}</h3>";
        echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
        echo "<p><strong>Result:</strong> " . count($result) . " rows returned</p>";
        
        if (count($result) > 0) {
            $first_row = $result[0];
            if (isset($first_row['count'])) {
                echo "<p><strong>Count:</strong> " . $first_row['count'] . "</p>";
            } else {
                echo "<pre>" . print_r(array_slice($result, 0, 3), true) . "</pre>";
            }
        }
        
        $passed_tests++;
        
    } catch (Exception $e) {
        echo "error'>";
        echo "<h3>❌ {$test_name}</h3>";
        echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}

// Test specific enhanced dashboard queries
echo "<h2>Enhanced Dashboard Query Tests</h2>";

$dashboard_queries = [
    'Student Stats' => "SELECT COUNT(*) as current_count, COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month FROM students WHERE academic_year = '2024-2025'",
    'Fee Collection Stats' => "SELECT COALESCE(SUM(amount_paid), 0) as total_this_month FROM fee_payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())",
    'Class Distribution' => "SELECT c.class_name, c.section, COUNT(s.id) as student_count FROM classes c LEFT JOIN students s ON c.id = s.class_id WHERE c.academic_year = '2024-2025' GROUP BY c.id ORDER BY c.class_name, c.section LIMIT 5",
    'Upcoming Events' => "SELECT * FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY event_date ASC LIMIT 3",
];

foreach ($dashboard_queries as $test_name => $query) {
    echo "<div class='test-result ";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        echo "success'>";
        echo "<h3>✅ {$test_name}</h3>";
        echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
        echo "<p><strong>Result:</strong> " . count($result) . " rows returned</p>";
        
        if (count($result) > 0) {
            echo "<pre>" . print_r(array_slice($result, 0, 2), true) . "</pre>";
        }
        
        $passed_tests++;
        
    } catch (Exception $e) {
        echo "error'>";
        echo "<h3>❌ {$test_name}</h3>";
        echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
    $total_tests++;
}

// Summary
echo "<div style='margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 8px;'>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> {$total_tests}</p>";
echo "<p><strong>Passed:</strong> {$passed_tests}</p>";
echo "<p><strong>Failed:</strong> " . ($total_tests - $passed_tests) . "</p>";

$pass_rate = round(($passed_tests / $total_tests) * 100, 1);
$color = $pass_rate >= 90 ? '#4CAF50' : ($pass_rate >= 70 ? '#ff9800' : '#f44336');

echo "<p style='font-size: 1.2em; font-weight: bold; color: {$color};'>Pass Rate: {$pass_rate}%</p>";

if ($pass_rate >= 90) {
    echo "<p>🎉 Excellent! Most database queries are working correctly.</p>";
} elseif ($pass_rate >= 70) {
    echo "<p>⚠️ Good, but some queries need attention.</p>";
} else {
    echo "<p>❌ Several database issues need to be fixed.</p>";
}

echo "</div>";

// Table structure information
echo "<h2>Database Table Information</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='test-result success'>";
    echo "<h3>Available Tables (" . count($tables) . ")</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result error'>";
    echo "<h3>❌ Cannot retrieve table list</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p style='margin-top: 30px; color: #666; font-size: 0.9em;'>Test completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
