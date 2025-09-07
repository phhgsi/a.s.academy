<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/performance.php';
require_once '../includes/cache.php';

// Check if user is admin
checkAdminAccess();

// Set JSON header
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'memory_usage':
            $memory_info = MemoryManager::getMemoryInfo();
            $response = [
                'success' => true,
                'current_usage_mb' => $memory_info['current_usage_mb'],
                'peak_usage_mb' => $memory_info['peak_usage_mb'],
                'limit' => $memory_info['limit']
            ];
            break;
            
        case 'cache_stats':
            $cache = CacheManager::getInstance();
            $stats = $cache->getStats();
            $response = [
                'success' => true,
                'stats' => $stats
            ];
            break;
            
        case 'database_metrics':
            $metrics = PerformanceDashboard::getMetrics();
            $response = [
                'success' => true,
                'metrics' => $metrics['database']
            ];
            break;
            
        case 'system_metrics':
            $metrics = PerformanceDashboard::getMetrics();
            $response = [
                'success' => true,
                'metrics' => $metrics['system']
            ];
            break;
            
        case 'full_metrics':
            $metrics = PerformanceDashboard::getMetrics();
            $response = [
                'success' => true,
                'metrics' => $metrics
            ];
            break;
            
        case 'query_analysis':
            $sql = $_POST['sql'] ?? '';
            $params = json_decode($_POST['params'] ?? '[]', true);
            
            if ($sql) {
                $optimizer = new QueryOptimizer($pdo);
                $analysis = $optimizer->analyzeQuery($sql, $params);
                
                $response = [
                    'success' => true,
                    'analysis' => $analysis
                ];
            } else {
                $response['message'] = 'SQL query is required';
            }
            break;
            
        case 'table_indexes':
            $table = $_GET['table'] ?? '';
            
            if ($table) {
                $analysis = analyzeTableIndexes($table);
                
                $response = [
                    'success' => true,
                    'analysis' => $analysis
                ];
            } else {
                $response['message'] = 'Table name is required';
            }
            break;
            
        case 'cache_clear':
            $deleted = cache_flush();
            $response = [
                'success' => true,
                'deleted_entries' => $deleted,
                'message' => "Cache cleared successfully. Deleted {$deleted} entries."
            ];
            break;
            
        case 'cache_cleanup':
            $deleted = cleanupCache();
            $response = [
                'success' => true,
                'deleted_entries' => $deleted,
                'message' => "Cache cleanup completed. Deleted {$deleted} expired entries."
            ];
            break;
            
        case 'cache_warmup':
            warmupCache();
            $response = [
                'success' => true,
                'message' => 'Cache warmed up successfully.'
            ];
            break;
            
        case 'performance_test':
            // Run a simple performance test
            $start_time = microtime(true);
            
            // Test database performance
            $db_start = microtime(true);
            $stmt = $pdo->query("SELECT COUNT(*) FROM students");
            $student_count = $stmt->fetchColumn();
            $db_time = microtime(true) - $db_start;
            
            // Test cache performance
            $cache_start = microtime(true);
            $cache = CacheManager::getInstance();
            $cache->set('test_key', 'test_value', 60);
            $cached_value = $cache->get('test_key');
            $cache->delete('test_key');
            $cache_time = microtime(true) - $cache_start;
            
            // Test memory allocation
            $memory_start = memory_get_usage();
            $test_array = range(1, 10000);
            unset($test_array);
            gc_collect_cycles();
            $memory_test_time = microtime(true) - $cache_start;
            
            $total_time = microtime(true) - $start_time;
            
            $response = [
                'success' => true,
                'test_results' => [
                    'total_time' => round($total_time * 1000, 2) . 'ms',
                    'database_time' => round($db_time * 1000, 2) . 'ms',
                    'cache_time' => round($cache_time * 1000, 2) . 'ms',
                    'memory_test_time' => round($memory_test_time * 1000, 2) . 'ms',
                    'student_count' => $student_count
                ],
                'message' => 'Performance test completed successfully.'
            ];
            break;
            
        case 'slow_queries':
            // Get slow queries from performance schema (if available)
            try {
                $stmt = $pdo->query("
                    SELECT 
                        DIGEST_TEXT as query,
                        COUNT_STAR as exec_count,
                        AVG_TIMER_WAIT/1000000000 as avg_time_seconds,
                        SUM_TIMER_WAIT/1000000000 as total_time_seconds
                    FROM performance_schema.events_statements_summary_by_digest 
                    WHERE AVG_TIMER_WAIT > 1000000000
                    ORDER BY AVG_TIMER_WAIT DESC 
                    LIMIT 10
                ");
                
                $slow_queries = $stmt->fetchAll();
                
                $response = [
                    'success' => true,
                    'slow_queries' => $slow_queries
                ];
                
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => 'Performance schema not available or accessible'
                ];
            }
            break;
            
        default:
            $response['message'] = 'Unknown action: ' . $action;
            break;
    }
    
} catch (Exception $e) {
    logError("Performance API error", [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $response = [
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
