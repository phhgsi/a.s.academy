<?php
/**
 * Database Health Check Utility
 * 
 * Simple script to test database connectivity and performance
 * Can be used for monitoring and operational diagnostics
 */

// Disable output buffering for real-time feedback
ob_end_clean();

// Set content type
header('Content-Type: text/plain');

echo "=== Database Health Check ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n\n";

try {
    // Include database connection
    require_once dirname(__DIR__) . '/includes/db_connection.php';
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    echo "✓ Database connection: SUCCESS\n";
    
    // Test basic query performance
    $start_time = microtime(true);
    $stmt = $pdo->query("SELECT 1 as test_value");
    $result = $stmt->fetch();
    $query_time = microtime(true) - $start_time;
    
    if ($result['test_value'] == 1) {
        echo "✓ Basic query test: SUCCESS (" . round($query_time * 1000, 2) . "ms)\n";
    } else {
        throw new Exception('Basic query returned unexpected result');
    }
    
    // Test table access
    $tables_to_check = ['users', 'students', 'classes', 'system_settings'];
    
    foreach ($tables_to_check as $table) {
        try {
            $start_time = microtime(true);
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $query_time = microtime(true) - $start_time;
            
            echo "✓ Table '$table': $count records (" . round($query_time * 1000, 2) . "ms)\n";
        } catch (Exception $e) {
            echo "✗ Table '$table': ERROR - " . $e->getMessage() . "\n";
        }
    }
    
    // Test database version
    try {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        echo "✓ MySQL Version: $version\n";
    } catch (Exception $e) {
        echo "⚠ Could not retrieve MySQL version\n";
    }
    
    // Test character set
    try {
        $stmt = $pdo->query("SELECT @@character_set_database as charset, @@collation_database as collation");
        $charset_info = $stmt->fetch();
        echo "✓ Character Set: {$charset_info['charset']} ({$charset_info['collation']})\n";
    } catch (Exception $e) {
        echo "⚠ Could not retrieve character set info\n";
    }
    
    // Test session management
    echo "\n--- Session Information ---\n";
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✓ Session status: ACTIVE\n";
        echo "✓ Session ID: " . session_id() . "\n";
        if (isset($_SESSION['current_academic_year'])) {
            echo "✓ Academic year: " . $_SESSION['current_academic_year'] . "\n";
        }
    } else {
        echo "⚠ Session status: INACTIVE\n";
    }
    
    // Test file permissions
    echo "\n--- File System Permissions ---\n";
    $directories_to_check = [
        dirname(__DIR__) . '/uploads',
        dirname(__DIR__) . '/logs',
        dirname(__DIR__) . '/includes'
    ];
    
    foreach ($directories_to_check as $dir) {
        if (is_dir($dir)) {
            if (is_writable($dir)) {
                echo "✓ Directory '$dir': WRITABLE\n";
            } else {
                echo "⚠ Directory '$dir': NOT WRITABLE\n";
            }
        } else {
            echo "✗ Directory '$dir': DOES NOT EXIST\n";
        }
    }
    
    echo "\n=== Health Check COMPLETED ===\n";
    echo "Overall Status: HEALTHY\n";
    
} catch (Exception $e) {
    echo "✗ HEALTH CHECK FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    http_response_code(503);
    exit(1);
}
?>
