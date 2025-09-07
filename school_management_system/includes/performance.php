<?php
/**
 * Performance Optimization Helper Functions
 * 
 * Features:
 * - Database query optimization
 * - Memory management
 * - Image compression and optimization
 * - Pagination helpers
 * - Index suggestions
 * - Query analysis
 */

require_once 'cache.php';
require_once 'error_handler.php';

/**
 * Database Query Optimizer
 */
class QueryOptimizer {
    
    private $pdo;
    private $slow_query_threshold = 1.0; // seconds
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Analyze query performance
     */
    public function analyzeQuery($sql, $params = []) {
        try {
            // Enable profiling
            $this->pdo->exec("SET profiling = 1");
            
            $start_time = microtime(true);
            $start_memory = memory_get_usage();
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            $execution_time = microtime(true) - $start_time;
            $memory_used = memory_get_usage() - $start_memory;
            
            // Get query profile
            $profile_stmt = $this->pdo->query("SHOW PROFILES");
            $profiles = $profile_stmt->fetchAll();
            $last_profile = end($profiles);
            
            $analysis = [
                'execution_time' => $execution_time,
                'memory_used' => $memory_used,
                'rows_returned' => count($result),
                'query_id' => $last_profile['Query_ID'] ?? null,
                'suggestions' => []
            ];
            
            // Add optimization suggestions
            if ($execution_time > $this->slow_query_threshold) {
                $analysis['suggestions'][] = 'Query execution time is slow. Consider adding indexes.';
            }
            
            if ($memory_used > 5242880) { // 5MB
                $analysis['suggestions'][] = 'High memory usage. Consider adding LIMIT clause or pagination.';
            }
            
            if (stripos($sql, 'SELECT *') !== false) {
                $analysis['suggestions'][] = 'Avoid SELECT *. Specify only needed columns.';
            }
            
            if (stripos($sql, 'ORDER BY') !== false && stripos($sql, 'LIMIT') === false) {
                $analysis['suggestions'][] = 'ORDER BY without LIMIT can be expensive. Consider pagination.';
            }
            
            return $analysis;
            
        } catch (Exception $e) {
            logError("Query analysis failed", [
                'query' => $sql,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Suggest indexes for slow queries
     */
    public function suggestIndexes($table, $where_columns = [], $order_columns = []) {
        $suggestions = [];
        
        // Suggest composite index for WHERE clauses
        if (!empty($where_columns)) {
            $index_name = "idx_{$table}_" . implode('_', $where_columns);
            $columns = implode(', ', $where_columns);
            $suggestions[] = "CREATE INDEX {$index_name} ON {$table} ({$columns});";
        }
        
        // Suggest index for ORDER BY columns
        if (!empty($order_columns)) {
            foreach ($order_columns as $column) {
                $index_name = "idx_{$table}_{$column}";
                $suggestions[] = "CREATE INDEX {$index_name} ON {$table} ({$column});";
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Check existing indexes on table
     */
    public function getTableIndexes($table) {
        try {
            $stmt = $this->pdo->prepare("SHOW INDEX FROM {$table}");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logError("Failed to get table indexes", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}

/**
 * Pagination Helper
 */
class PaginationHelper {
    
    private $page;
    private $per_page;
    private $total_records;
    private $total_pages;
    
    public function __construct($page = 1, $per_page = 20, $total_records = 0) {
        $this->page = max(1, (int)$page);
        $this->per_page = max(1, min(100, (int)$per_page)); // Limit max per page
        $this->total_records = max(0, (int)$total_records);
        $this->total_pages = ceil($this->total_records / $this->per_page);
    }
    
    public function getOffset() {
        return ($this->page - 1) * $this->per_page;
    }
    
    public function getLimit() {
        return $this->per_page;
    }
    
    public function getLimitClause() {
        return "LIMIT " . $this->getLimit() . " OFFSET " . $this->getOffset();
    }
    
    public function getPaginationInfo() {
        $start = $this->getOffset() + 1;
        $end = min($start + $this->per_page - 1, $this->total_records);
        
        return [
            'current_page' => $this->page,
            'per_page' => $this->per_page,
            'total_records' => $this->total_records,
            'total_pages' => $this->total_pages,
            'start_record' => $start,
            'end_record' => $end,
            'has_previous' => $this->page > 1,
            'has_next' => $this->page < $this->total_pages,
            'previous_page' => max(1, $this->page - 1),
            'next_page' => min($this->total_pages, $this->page + 1)
        ];
    }
    
    /**
     * Generate pagination HTML
     */
    public function renderPagination($base_url, $url_params = []) {
        if ($this->total_pages <= 1) {
            return '';
        }
        
        $info = $this->getPaginationInfo();
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Previous button
        if ($info['has_previous']) {
            $url = $this->buildUrl($base_url, array_merge($url_params, ['page' => $info['previous_page']]));
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">&laquo; Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
        }
        
        // Page numbers
        $start_page = max(1, $this->page - 2);
        $end_page = min($this->total_pages, $this->page + 2);
        
        if ($start_page > 1) {
            $url = $this->buildUrl($base_url, array_merge($url_params, ['page' => 1]));
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">1</a></li>';
            if ($start_page > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $this->page) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $url = $this->buildUrl($base_url, array_merge($url_params, ['page' => $i]));
                $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">' . $i . '</a></li>';
            }
        }
        
        if ($end_page < $this->total_pages) {
            if ($end_page < $this->total_pages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $url = $this->buildUrl($base_url, array_merge($url_params, ['page' => $this->total_pages]));
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">' . $this->total_pages . '</a></li>';
        }
        
        // Next button
        if ($info['has_next']) {
            $url = $this->buildUrl($base_url, array_merge($url_params, ['page' => $info['next_page']]));
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">Next &raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
        }
        
        $html .= '</ul></nav>';
        
        // Add pagination info
        $html .= '<div class="pagination-info text-muted small mt-2">';
        $html .= "Showing {$info['start_record']} to {$info['end_record']} of {$info['total_records']} records";
        $html .= '</div>';
        
        return $html;
    }
    
    private function buildUrl($base_url, $params) {
        $query_string = http_build_query($params);
        $separator = strpos($base_url, '?') !== false ? '&' : '?';
        return $base_url . ($query_string ? $separator . $query_string : '');
    }
}

/**
 * Image Optimization
 */
class ImageOptimizer {
    
    private static $supported_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private static $max_width = 1920;
    private static $max_height = 1080;
    private static $quality = 85;
    
    /**
     * Optimize uploaded image
     */
    public static function optimizeImage($source_path, $destination_path = null, $max_width = null, $max_height = null, $quality = null) {
        $destination_path = $destination_path ?: $source_path;
        $max_width = $max_width ?: self::$max_width;
        $max_height = $max_height ?: self::$max_height;
        $quality = $quality ?: self::$quality;
        
        if (!file_exists($source_path)) {
            return false;
        }
        
        $image_info = getimagesize($source_path);
        if (!$image_info) {
            return false;
        }
        
        $original_width = $image_info[0];
        $original_height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Skip if image is already small enough
        if ($original_width <= $max_width && $original_height <= $max_height) {
            if ($source_path !== $destination_path) {
                copy($source_path, $destination_path);
            }
            return true;
        }
        
        // Calculate new dimensions
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        $new_width = round($original_width * $ratio);
        $new_height = round($original_height * $ratio);
        
        // Create image resource based on type
        switch ($mime_type) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;
            case 'image/webp':
                $source_image = imagecreatefromwebp($source_path);
                break;
            default:
                return false;
        }
        
        if (!$source_image) {
            return false;
        }
        
        // Create optimized image
        $optimized_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagealphablending($optimized_image, false);
            imagesavealpha($optimized_image, true);
            $transparent = imagecolorallocatealpha($optimized_image, 255, 255, 255, 127);
            imagefill($optimized_image, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $optimized_image, $source_image,
            0, 0, 0, 0,
            $new_width, $new_height,
            $original_width, $original_height
        );
        
        // Save optimized image
        $success = false;
        switch ($mime_type) {
            case 'image/jpeg':
                $success = imagejpeg($optimized_image, $destination_path, $quality);
                break;
            case 'image/png':
                $success = imagepng($optimized_image, $destination_path);
                break;
            case 'image/gif':
                $success = imagegif($optimized_image, $destination_path);
                break;
            case 'image/webp':
                $success = imagewebp($optimized_image, $destination_path, $quality);
                break;
        }
        
        // Cleanup
        imagedestroy($source_image);
        imagedestroy($optimized_image);
        
        if ($success) {
            logInfo("Image optimized", [
                'source' => $source_path,
                'destination' => $destination_path,
                'original_size' => filesize($source_path),
                'optimized_size' => filesize($destination_path),
                'compression_ratio' => round((1 - filesize($destination_path) / filesize($source_path)) * 100, 2)
            ]);
        }
        
        return $success;
    }
    
    /**
     * Generate thumbnail
     */
    public static function generateThumbnail($source_path, $thumbnail_path, $width = 150, $height = 150) {
        return self::optimizeImage($source_path, $thumbnail_path, $width, $height, 90);
    }
    
    /**
     * Bulk optimize images in directory
     */
    public static function bulkOptimize($directory, $recursive = true) {
        $optimized = 0;
        $total_saved = 0;
        
        $iterator = $recursive ? 
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) :
            new DirectoryIterator($directory);
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                if (in_array($extension, self::$supported_types)) {
                    $file_path = $file->getPathname();
                    $original_size = filesize($file_path);
                    
                    if (self::optimizeImage($file_path)) {
                        $new_size = filesize($file_path);
                        $saved = $original_size - $new_size;
                        $total_saved += $saved;
                        $optimized++;
                    }
                }
            }
        }
        
        logInfo("Bulk image optimization completed", [
            'directory' => $directory,
            'optimized_count' => $optimized,
            'total_saved_bytes' => $total_saved,
            'total_saved_mb' => round($total_saved / 1024 / 1024, 2)
        ]);
        
        return ['optimized' => $optimized, 'saved_bytes' => $total_saved];
    }
}

/**
 * Memory Management Helper
 */
class MemoryManager {
    
    /**
     * Process large datasets in chunks
     */
    public static function processInChunks($data, $callback, $chunk_size = 100) {
        $total_processed = 0;
        $chunks = array_chunk($data, $chunk_size);
        
        foreach ($chunks as $chunk) {
            $callback($chunk);
            $total_processed += count($chunk);
            
            // Force garbage collection
            if ($total_processed % ($chunk_size * 10) === 0) {
                gc_collect_cycles();
            }
        }
        
        return $total_processed;
    }
    
    /**
     * Stream large CSV files
     */
    public static function streamCSV($file_path, $callback, $has_header = true) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return false;
        }
        
        $row_count = 0;
        $header = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            if ($has_header && $row_count === 0) {
                $header = $row;
            } else {
                $data = $has_header ? array_combine($header, $row) : $row;
                $callback($data, $row_count);
            }
            
            $row_count++;
            
            // Periodic garbage collection
            if ($row_count % 1000 === 0) {
                gc_collect_cycles();
            }
        }
        
        fclose($handle);
        return $row_count;
    }
    
    /**
     * Get memory usage information
     */
    public static function getMemoryInfo() {
        return [
            'current_usage' => memory_get_usage(true),
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage' => memory_get_peak_usage(true),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit')
        ];
    }
}

/**
 * Database Connection Pool
 */
class ConnectionPool {
    
    private static $instance = null;
    private $connections = [];
    private $max_connections = 5;
    private $current_connections = 0;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Try to reuse existing connection
        foreach ($this->connections as $key => $connection) {
            if (!$connection['in_use']) {
                $this->connections[$key]['in_use'] = true;
                $this->connections[$key]['last_used'] = time();
                return $connection['pdo'];
            }
        }
        
        // Create new connection if under limit
        if ($this->current_connections < $this->max_connections) {
            return $this->createNewConnection();
        }
        
        // Wait for available connection (simplified)
        usleep(100000); // 100ms
        return $this->getConnection();
    }
    
    public function releaseConnection($pdo) {
        foreach ($this->connections as $key => $connection) {
            if ($connection['pdo'] === $pdo) {
                $this->connections[$key]['in_use'] = false;
                break;
            }
        }
    }
    
    private function createNewConnection() {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            $connection_id = uniqid();
            $this->connections[$connection_id] = [
                'pdo' => $pdo,
                'in_use' => true,
                'created_at' => time(),
                'last_used' => time()
            ];
            
            $this->current_connections++;
            
            return $pdo;
            
        } catch (PDOException $e) {
            logError("Failed to create database connection", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

/**
 * Performance Monitoring Dashboard
 */
class PerformanceDashboard {
    
    /**
     * Get system performance metrics
     */
    public static function getMetrics() {
        $cache = CacheManager::getInstance();
        $cache_stats = $cache->getStats();
        
        return [
            'memory' => MemoryManager::getMemoryInfo(),
            'cache' => $cache_stats,
            'database' => self::getDatabaseMetrics(),
            'system' => self::getSystemMetrics()
        ];
    }
    
    private static function getDatabaseMetrics() {
        global $pdo;
        
        try {
            // Get database status
            $stmt = $pdo->query("SHOW STATUS LIKE 'Queries'");
            $queries = $stmt->fetch();
            
            $stmt = $pdo->query("SHOW STATUS LIKE 'Uptime'");
            $uptime = $stmt->fetch();
            
            $stmt = $pdo->query("SHOW STATUS LIKE 'Slow_queries'");
            $slow_queries = $stmt->fetch();
            
            return [
                'total_queries' => $queries['Value'] ?? 0,
                'slow_queries' => $slow_queries['Value'] ?? 0,
                'uptime_seconds' => $uptime['Value'] ?? 0,
                'queries_per_second' => $uptime['Value'] > 0 ? round($queries['Value'] / $uptime['Value'], 2) : 0
            ];
            
        } catch (Exception $e) {
            logError("Failed to get database metrics", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    private static function getSystemMetrics() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'disk_free_space' => disk_free_space('.'),
            'disk_free_space_gb' => round(disk_free_space('.') / 1024 / 1024 / 1024, 2),
            'load_average' => sys_getloadavg()
        ];
    }
}

/**
 * Global Performance Helper Functions
 */

/**
 * Optimized student listing with pagination
 */
function getStudentsPaginated($page = 1, $per_page = 20, $filters = []) {
    global $pdo;
    
    // Build WHERE clause
    $where_conditions = ["s.is_active = 1"];
    $params = [];
    
    if (!empty($filters['class_id'])) {
        $where_conditions[] = "s.class_id = ?";
        $params[] = $filters['class_id'];
    }
    
    if (!empty($filters['academic_year'])) {
        $where_conditions[] = "s.academic_year = ?";
        $params[] = $filters['academic_year'];
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ?)";
        $search_term = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM students s $where_clause";
    $total_records = cached_value($count_sql, $params, 300);
    
    // Create pagination helper
    $pagination = new PaginationHelper($page, $per_page, $total_records);
    
    // Get paginated results
    $sql = "
        SELECT s.*, c.class_name, c.section 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        $where_clause 
        ORDER BY s.first_name, s.last_name 
        {$pagination->getLimitClause()}
    ";
    
    $cache_key = "students_page_{$page}_" . md5(serialize($filters));
    $students = cached_query($sql, $params, 300, ['table_students']);
    
    return [
        'students' => $students,
        'pagination' => $pagination->getPaginationInfo(),
        'pagination_html' => $pagination->renderPagination($_SERVER['REQUEST_URI'])
    ];
}

/**
 * Optimized fee collection report
 */
function getFeeCollectionReport($start_date, $end_date, $class_id = null) {
    global $pdo;
    
    $params = [$start_date, $end_date];
    $where_clause = "WHERE fp.payment_date BETWEEN ? AND ?";
    
    if ($class_id) {
        $where_clause .= " AND s.class_id = ?";
        $params[] = $class_id;
    }
    
    $cache_key = "fee_report_" . md5(serialize($params));
    
    return cache_remember($cache_key, function() use ($pdo, $where_clause, $params) {
        $sql = "
            SELECT 
                DATE(fp.payment_date) as payment_date,
                COUNT(*) as transaction_count,
                SUM(fp.amount) as total_amount,
                c.class_name,
                ft.fee_type_name
            FROM fee_payments fp
            LEFT JOIN students s ON fp.student_id = s.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN fee_types ft ON fp.fee_type_id = ft.id
            $where_clause
            GROUP BY DATE(fp.payment_date), c.class_name, ft.fee_type_name
            ORDER BY fp.payment_date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }, 1800); // Cache for 30 minutes
}

/**
 * Optimized attendance report
 */
function getAttendanceReport($class_id, $start_date, $end_date) {
    global $pdo;
    
    $cache_key = "attendance_report_{$class_id}_{$start_date}_{$end_date}";
    
    return cache_remember($cache_key, function() use ($pdo, $class_id, $start_date, $end_date) {
        $sql = "
            SELECT 
                s.id,
                s.first_name,
                s.last_name,
                s.admission_number,
                COUNT(a.id) as total_days,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as attendance_percentage
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id 
                AND a.date BETWEEN ? AND ?
            WHERE s.class_id = ? AND s.is_active = 1
            GROUP BY s.id
            ORDER BY attendance_percentage DESC, s.first_name
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start_date, $end_date, $class_id]);
        return $stmt->fetchAll();
    }, 600); // Cache for 10 minutes
}

/**
 * Database Index Analyzer
 */
function analyzeTableIndexes($table_name) {
    global $pdo;
    
    try {
        // Get table indexes
        $stmt = $pdo->prepare("SHOW INDEX FROM {$table_name}");
        $stmt->execute();
        $indexes = $stmt->fetchAll();
        
        // Get table status
        $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE ?");
        $stmt->execute([$table_name]);
        $table_status = $stmt->fetch();
        
        // Analyze index usage
        $index_analysis = [];
        foreach ($indexes as $index) {
            $index_name = $index['Key_name'];
            if (!isset($index_analysis[$index_name])) {
                $index_analysis[$index_name] = [
                    'columns' => [],
                    'unique' => $index['Non_unique'] == 0,
                    'type' => $index['Index_type']
                ];
            }
            $index_analysis[$index_name]['columns'][] = $index['Column_name'];
        }
        
        return [
            'table_name' => $table_name,
            'row_count' => $table_status['Rows'] ?? 0,
            'data_length' => $table_status['Data_length'] ?? 0,
            'index_length' => $table_status['Index_length'] ?? 0,
            'indexes' => $index_analysis
        ];
        
    } catch (Exception $e) {
        logError("Failed to analyze table indexes", [
            'table' => $table_name,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Compression Helper
 */
class CompressionHelper {
    
    /**
     * Compress HTML output
     */
    public static function compressHTML($html) {
        // Remove unnecessary whitespace
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Remove comments (but preserve IE conditional comments)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        
        // Remove extra whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
    
    /**
     * Enable GZIP compression
     */
    public static function enableGzipCompression() {
        if (!ob_get_level()) {
            if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
                if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
                    ob_start('ob_gzhandler');
                    return true;
                }
            }
        }
        return false;
    }
}

/**
 * Asset Bundling and Minification
 */
function generateAssetBundle($type = 'css', $files = []) {
    $bundle_key = $type . '_bundle_' . md5(serialize($files));
    
    return cache_remember($bundle_key, function() use ($type, $files) {
        $content = '';
        $last_modified = 0;
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $file_content = file_get_contents($file);
                
                if ($type === 'css') {
                    $content .= AssetOptimizer::minifyCSS($file_content) . "\n";
                } elseif ($type === 'js') {
                    $content .= AssetOptimizer::minifyJS($file_content) . "\n";
                } else {
                    $content .= $file_content . "\n";
                }
                
                $last_modified = max($last_modified, filemtime($file));
            }
        }
        
        return [
            'content' => $content,
            'last_modified' => $last_modified,
            'size' => strlen($content)
        ];
    }, 3600); // Cache for 1 hour
}

/**
 * Session optimization
 */
function optimizeSession() {
    // Use database session handler for better performance
    if (!session_id()) {
        ini_set('session.gc_maxlifetime', 7200); // 2 hours
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_only_cookies', 1);
        
        session_start();
    }
}

/**
 * Performance monitoring middleware
 */
function startPerformanceMonitoring($operation_name) {
    PerformanceMonitor::start($operation_name);
    
    // Set response time header
    if (!headers_sent()) {
        header('X-Performance-Start: ' . microtime(true));
    }
}

function endPerformanceMonitoring($operation_name) {
    $stats = PerformanceMonitor::end($operation_name);
    
    // Set response time header
    if (!headers_sent() && $stats) {
        header('X-Performance-Time: ' . $stats['execution_time']);
        header('X-Performance-Memory: ' . $stats['memory_used']);
    }
    
    return $stats;
}

/**
 * Database query builder with optimization
 */
class OptimizedQueryBuilder {
    
    private $table;
    private $select = ['*'];
    private $where = [];
    private $joins = [];
    private $order = [];
    private $limit = null;
    private $offset = null;
    private $params = [];
    private $cache_ttl = 600;
    private $cache_tags = [];
    
    public function __construct($table) {
        $this->table = $table;
        $this->cache_tags[] = "table_{$table}";
    }
    
    public function select($columns) {
        $this->select = is_array($columns) ? $columns : [$columns];
        return $this;
    }
    
    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = "{$column} {$operator} ?";
        $this->params[] = $value;
        return $this;
    }
    
    public function join($table, $condition) {
        $this->joins[] = "JOIN {$table} ON {$condition}";
        $this->cache_tags[] = "table_{$table}";
        return $this;
    }
    
    public function leftJoin($table, $condition) {
        $this->joins[] = "LEFT JOIN {$table} ON {$condition}";
        $this->cache_tags[] = "table_{$table}";
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->order[] = "{$column} {$direction}";
        return $this;
    }
    
    public function limit($limit, $offset = 0) {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
    
    public function cacheTTL($ttl) {
        $this->cache_ttl = $ttl;
        return $this;
    }
    
    public function get() {
        $sql = $this->buildSQL();
        
        global $pdo;
        $query_cache = new QueryCache($pdo);
        
        return $query_cache->query($sql, $this->params, $this->cache_ttl, $this->cache_tags);
    }
    
    public function first() {
        $this->limit(1);
        $results = $this->get();
        return $results ? $results[0] : null;
    }
    
    public function count() {
        $this->select = ['COUNT(*) as count'];
        $result = $this->first();
        return $result ? $result['count'] : 0;
    }
    
    private function buildSQL() {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        if (!empty($this->order)) {
            $sql .= " ORDER BY " . implode(', ', $this->order);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        
        return $sql;
    }
}

// Helper function to create optimized query builder
function table($table_name) {
    return new OptimizedQueryBuilder($table_name);
}

// Initialize performance optimization
optimizeSession();
CompressionHelper::enableGzipCompression();

// Cleanup cache periodically (1% chance)
if (mt_rand(1, 100) === 1) {
    cleanupCache();
}
?>
