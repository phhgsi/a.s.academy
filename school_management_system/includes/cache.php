<?php
/**
 * Performance Optimization and Caching System
 * 
 * Features:
 * - File-based caching for database queries
 * - Page fragment caching
 * - Session-based caching
 * - Cache invalidation strategies
 * - Performance monitoring
 * - Automatic cache cleanup
 * - Memory usage optimization
 */

class CacheManager {
    
    private static $instance = null;
    private $cache_dir;
    private $default_ttl = 3600; // 1 hour
    private $max_cache_size = 104857600; // 100MB
    private $enabled = true;
    
    public function __construct() {
        $this->cache_dir = __DIR__ . '/../cache';
        $this->ensureCacheDirectory();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Store data in cache
     */
    public function set($key, $data, $ttl = null) {
        if (!$this->enabled) return false;
        
        $ttl = $ttl ?? $this->default_ttl;
        $cache_file = $this->getCacheFile($key);
        
        $cache_data = [
            'data' => $data,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
            'hits' => 0
        ];
        
        $success = file_put_contents($cache_file, serialize($cache_data), LOCK_EX) !== false;
        
        if ($success) {
            $this->cleanupIfNeeded();
        }
        
        return $success;
    }
    
    /**
     * Retrieve data from cache
     */
    public function get($key, $default = null) {
        if (!$this->enabled) return $default;
        
        $cache_file = $this->getCacheFile($key);
        
        if (!file_exists($cache_file)) {
            return $default;
        }
        
        $cache_data = @unserialize(file_get_contents($cache_file));
        
        if (!$cache_data || !is_array($cache_data)) {
            $this->delete($key);
            return $default;
        }
        
        // Check if expired
        if (time() > $cache_data['expires_at']) {
            $this->delete($key);
            return $default;
        }
        
        // Update hit count
        $cache_data['hits']++;
        file_put_contents($cache_file, serialize($cache_data), LOCK_EX);
        
        return $cache_data['data'];
    }
    
    /**
     * Check if cache key exists and is not expired
     */
    public function has($key) {
        if (!$this->enabled) return false;
        
        $cache_file = $this->getCacheFile($key);
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $cache_data = @unserialize(file_get_contents($cache_file));
        
        if (!$cache_data || time() > $cache_data['expires_at']) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Delete cache entry
     */
    public function delete($key) {
        $cache_file = $this->getCacheFile($key);
        
        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get or set cache with callback
     */
    public function remember($key, $callback, $ttl = null) {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Cache database query results
     */
    public function cacheQuery($query, $params, $callback, $ttl = 600) {
        $cache_key = 'query_' . md5($query . serialize($params));
        
        return $this->remember($cache_key, $callback, $ttl);
    }
    
    /**
     * Cache user-specific data
     */
    public function cacheForUser($user_id, $key, $data, $ttl = null) {
        $user_key = "user_{$user_id}_{$key}";
        return $this->set($user_key, $data, $ttl);
    }
    
    public function getForUser($user_id, $key, $default = null) {
        $user_key = "user_{$user_id}_{$key}";
        return $this->get($user_key, $default);
    }
    
    /**
     * Tag-based cache invalidation
     */
    public function setWithTags($key, $data, $tags = [], $ttl = null) {
        $success = $this->set($key, $data, $ttl);
        
        if ($success && !empty($tags)) {
            foreach ($tags as $tag) {
                $this->addToTag($tag, $key);
            }
        }
        
        return $success;
    }
    
    public function invalidateTag($tag) {
        $tag_file = $this->cache_dir . "/tags/{$tag}.tag";
        
        if (file_exists($tag_file)) {
            $keys = @unserialize(file_get_contents($tag_file));
            
            if (is_array($keys)) {
                foreach ($keys as $key) {
                    $this->delete($key);
                }
            }
            
            unlink($tag_file);
        }
    }
    
    private function addToTag($tag, $key) {
        $tag_dir = $this->cache_dir . '/tags';
        if (!is_dir($tag_dir)) {
            mkdir($tag_dir, 0755, true);
        }
        
        $tag_file = $tag_dir . "/{$tag}.tag";
        $keys = [];
        
        if (file_exists($tag_file)) {
            $keys = @unserialize(file_get_contents($tag_file)) ?: [];
        }
        
        $keys[] = $key;
        $keys = array_unique($keys);
        
        file_put_contents($tag_file, serialize($keys), LOCK_EX);
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*.cache');
        $total_size = 0;
        $total_files = 0;
        $expired_files = 0;
        $total_hits = 0;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $total_size += $size;
            $total_files++;
            
            $cache_data = @unserialize(file_get_contents($file));
            if ($cache_data) {
                $total_hits += $cache_data['hits'] ?? 0;
                
                if (time() > $cache_data['expires_at']) {
                    $expired_files++;
                }
            }
        }
        
        return [
            'total_files' => $total_files,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'expired_files' => $expired_files,
            'total_hits' => $total_hits,
            'hit_rate' => $total_files > 0 ? round(($total_hits / $total_files), 2) : 0
        ];
    }
    
    /**
     * Clean up expired cache entries
     */
    public function cleanup() {
        $files = glob($this->cache_dir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $cache_data = @unserialize(file_get_contents($file));
            
            if (!$cache_data || time() > $cache_data['expires_at']) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        $safe_key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $hash = md5($key);
        
        // Create subdirectories to avoid too many files in one directory
        $subdir = substr($hash, 0, 2);
        $cache_subdir = $this->cache_dir . '/' . $subdir;
        
        if (!is_dir($cache_subdir)) {
            mkdir($cache_subdir, 0755, true);
        }
        
        return $cache_subdir . '/' . $safe_key . '_' . $hash . '.cache';
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        // Create .htaccess to protect cache files
        $htaccess_file = $this->cache_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Deny from all\n");
        }
    }
    
    /**
     * Clean up cache if it gets too large
     */
    private function cleanupIfNeeded() {
        $stats = $this->getStats();
        
        if ($stats['total_size'] > $this->max_cache_size) {
            // Delete oldest files first
            $files = glob($this->cache_dir . '/*/*.cache');
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $deleted = 0;
            $target_size = $this->max_cache_size * 0.8; // Reduce to 80% of max
            
            foreach ($files as $file) {
                if (unlink($file)) {
                    $deleted++;
                    $stats['total_size'] -= filesize($file);
                    
                    if ($stats['total_size'] <= $target_size) {
                        break;
                    }
                }
            }
        }
    }
}

/**
 * Query Cache Helper
 */
class QueryCache {
    
    private $cache;
    private $pdo;
    
    public function __construct($pdo) {
        $this->cache = CacheManager::getInstance();
        $this->pdo = $pdo;
    }
    
    /**
     * Execute cached query
     */
    public function query($sql, $params = [], $ttl = 600, $tags = []) {
        $cache_key = 'query_' . md5($sql . serialize($params));
        
        // Try to get from cache first
        $cached_result = $this->cache->get($cache_key);
        if ($cached_result !== null) {
            return $cached_result;
        }
        
        // Execute query and cache result
        try {
            $start_time = microtime(true);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            $execution_time = microtime(true) - $start_time;
            
            // Log slow queries
            if ($execution_time > 1) {
                logWarning("Slow Query Detected", [
                    'query' => $sql,
                    'params' => $params,
                    'execution_time' => $execution_time
                ]);
            }
            
            // Cache the result
            if (!empty($tags)) {
                $this->cache->setWithTags($cache_key, $result, $tags, $ttl);
            } else {
                $this->cache->set($cache_key, $result, $ttl);
            }
            
            return $result;
            
        } catch (Exception $e) {
            logError("Query execution failed", [
                'query' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get single row with caching
     */
    public function queryRow($sql, $params = [], $ttl = 600, $tags = []) {
        $result = $this->query($sql, $params, $ttl, $tags);
        return $result ? $result[0] : null;
    }
    
    /**
     * Get single value with caching
     */
    public function queryValue($sql, $params = [], $ttl = 600, $tags = []) {
        $row = $this->queryRow($sql, $params, $ttl, $tags);
        return $row ? array_values($row)[0] : null;
    }
    
    /**
     * Invalidate cache by table name
     */
    public function invalidateTable($table_name) {
        $this->cache->invalidateTag("table_{$table_name}");
    }
    
    /**
     * Invalidate cache by user
     */
    public function invalidateUser($user_id) {
        $this->cache->invalidateTag("user_{$user_id}");
    }
}

/**
 * Page Fragment Caching
 */
class FragmentCache {
    
    private $cache;
    private $fragments = [];
    
    public function __construct() {
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Start caching a fragment
     */
    public function start($key, $ttl = 3600) {
        // Check if fragment exists in cache
        $cached_content = $this->cache->get("fragment_{$key}");
        
        if ($cached_content !== null) {
            echo $cached_content;
            return false; // Don't capture output
        }
        
        // Start output buffering
        $this->fragments[$key] = [
            'ttl' => $ttl,
            'start_time' => microtime(true)
        ];
        
        ob_start();
        return true; // Capture output
    }
    
    /**
     * End fragment caching
     */
    public function end($key) {
        if (!isset($this->fragments[$key])) {
            return false;
        }
        
        $content = ob_get_clean();
        $fragment_info = $this->fragments[$key];
        
        // Cache the fragment
        $this->cache->set("fragment_{$key}", $content, $fragment_info['ttl']);
        
        // Output the content
        echo $content;
        
        // Log performance
        $execution_time = microtime(true) - $fragment_info['start_time'];
        logDebug("Fragment cached", [
            'key' => $key,
            'size' => strlen($content),
            'execution_time' => $execution_time
        ]);
        
        unset($this->fragments[$key]);
        return true;
    }
    
    /**
     * Helper method for simple fragment caching
     */
    public function fragment($key, $callback, $ttl = 3600) {
        $cached_content = $this->cache->get("fragment_{$key}");
        
        if ($cached_content !== null) {
            return $cached_content;
        }
        
        ob_start();
        $callback();
        $content = ob_get_clean();
        
        $this->cache->set("fragment_{$key}", $content, $ttl);
        
        return $content;
    }
}

/**
 * Configuration and Settings Cache
 */
class ConfigCache {
    
    private static $settings = null;
    private static $cache = null;
    
    public static function get($key, $default = null) {
        if (self::$settings === null) {
            self::loadSettings();
        }
        
        return self::$settings[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        global $pdo;
        
        try {
            // Update in database
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_by) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$key, $value, $_SESSION['user_id'] ?? null]);
            
            // Update cache
            if (self::$settings !== null) {
                self::$settings[$key] = $value;
                self::saveSettingsToCache();
            }
            
            return true;
            
        } catch (Exception $e) {
            logError("Failed to update setting", [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public static function refresh() {
        self::$settings = null;
        if (self::$cache) {
            self::$cache->delete('system_settings');
        }
        self::loadSettings();
    }
    
    private static function loadSettings() {
        self::$cache = CacheManager::getInstance();
        
        // Try to get from cache first
        self::$settings = self::$cache->get('system_settings');
        
        if (self::$settings === null) {
            self::loadSettingsFromDatabase();
        }
    }
    
    private static function loadSettingsFromDatabase() {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            self::$settings = $settings;
            self::saveSettingsToCache();
            
        } catch (Exception $e) {
            logError("Failed to load settings", ['error' => $e->getMessage()]);
            self::$settings = [];
        }
    }
    
    private static function saveSettingsToCache() {
        if (self::$cache && self::$settings) {
            self::$cache->set('system_settings', self::$settings, 3600); // Cache for 1 hour
        }
    }
}

/**
 * Performance Monitor
 */
class PerformanceMonitor {
    
    private static $timers = [];
    private static $memory_checkpoints = [];
    
    public static function start($name) {
        self::$timers[$name] = microtime(true);
        self::$memory_checkpoints[$name] = memory_get_usage(true);
    }
    
    public static function end($name) {
        if (!isset(self::$timers[$name])) {
            return null;
        }
        
        $execution_time = microtime(true) - self::$timers[$name];
        $memory_used = memory_get_usage(true) - self::$memory_checkpoints[$name];
        
        $stats = [
            'execution_time' => $execution_time,
            'memory_used' => $memory_used,
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        // Log if performance is poor
        if ($execution_time > 1 || $memory_used > 10485760) { // > 1 second or > 10MB
            logWarning("Performance issue detected", [
                'operation' => $name,
                'stats' => $stats
            ]);
        }
        
        unset(self::$timers[$name]);
        unset(self::$memory_checkpoints[$name]);
        
        return $stats;
    }
    
    public static function measure($name, $callback) {
        self::start($name);
        $result = $callback();
        $stats = self::end($name);
        
        return ['result' => $result, 'stats' => $stats];
    }
}

/**
 * Database Connection Optimization
 */
class OptimizedDatabase {
    
    private static $instance = null;
    private $pdo;
    private $query_cache;
    private $connection_pool = [];
    
    public function __construct() {
        $this->initializeConnection();
        $this->query_cache = new QueryCache($this->pdo);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeConnection() {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => true, // Use persistent connections
            ];
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set MySQL-specific optimizations
            $this->pdo->exec("SET SESSION query_cache_type = ON");
            $this->pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
            
        } catch (PDOException $e) {
            logError("Database connection failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function getQueryCache() {
        return $this->query_cache;
    }
    
    /**
     * Execute optimized query with caching
     */
    public function cachedQuery($sql, $params = [], $ttl = 600, $tags = []) {
        return $this->query_cache->query($sql, $params, $ttl, $tags);
    }
    
    /**
     * Batch insert optimization
     */
    public function batchInsert($table, $data, $batch_size = 100) {
        if (empty($data)) return 0;
        
        $this->pdo->beginTransaction();
        
        try {
            $fields = array_keys($data[0]);
            $placeholders = '(' . str_repeat('?,', count($fields) - 1) . '?)';
            
            $inserted = 0;
            $batches = array_chunk($data, $batch_size);
            
            foreach ($batches as $batch) {
                $values = str_repeat($placeholders . ',', count($batch) - 1) . $placeholders;
                $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES {$values}";
                
                $flat_data = [];
                foreach ($batch as $row) {
                    foreach ($fields as $field) {
                        $flat_data[] = $row[$field];
                    }
                }
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($flat_data);
                $inserted += $stmt->rowCount();
            }
            
            $this->pdo->commit();
            
            // Invalidate related caches
            CacheManager::getInstance()->invalidateTag("table_{$table}");
            
            return $inserted;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            logError("Batch insert failed", [
                'table' => $table,
                'batch_size' => $batch_size,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

/**
 * Static Asset Optimization
 */
class AssetOptimizer {
    
    private static $minified_cache = [];
    
    /**
     * Minify CSS content
     */
    public static function minifyCSS($css) {
        if (isset(self::$minified_cache[md5($css)])) {
            return self::$minified_cache[md5($css)];
        }
        
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary spaces around certain characters
        $css = str_replace([' {', '{ ', ' }', '} ', '; ', ' ;', ': ', ' :', ', ', ' ,'], 
                          ['{', '{', '}', '}', ';', ';', ':', ':', ',', ','], $css);
        
        $css = trim($css);
        
        self::$minified_cache[md5($css)] = $css;
        return $css;
    }
    
    /**
     * Minify JavaScript content
     */
    public static function minifyJS($js) {
        if (isset(self::$minified_cache[md5($js)])) {
            return self::$minified_cache[md5($js)];
        }
        
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = trim($js);
        
        self::$minified_cache[md5($js)] = $js;
        return $js;
    }
    
    /**
     * Combine and minify CSS files
     */
    public static function combineCSSFiles($files, $output_file = null) {
        $combined_css = '';
        $last_modified = 0;
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $combined_css .= file_get_contents($file) . "\n";
                $last_modified = max($last_modified, filemtime($file));
            }
        }
        
        $minified_css = self::minifyCSS($combined_css);
        
        if ($output_file) {
            file_put_contents($output_file, $minified_css);
        }
        
        return $minified_css;
    }
    
    /**
     * Generate cache-busting URLs
     */
    public static function assetUrl($file_path) {
        if (file_exists($file_path)) {
            $version = filemtime($file_path);
            return $file_path . '?v=' . $version;
        }
        
        return $file_path;
    }
}

/**
 * Global helper functions
 */
function cache_get($key, $default = null) {
    return CacheManager::getInstance()->get($key, $default);
}

function cache_set($key, $data, $ttl = null) {
    return CacheManager::getInstance()->set($key, $data, $ttl);
}

function cache_remember($key, $callback, $ttl = null) {
    return CacheManager::getInstance()->remember($key, $callback, $ttl);
}

function cache_forget($key) {
    return CacheManager::getInstance()->delete($key);
}

function cache_flush() {
    return CacheManager::getInstance()->clear();
}

/**
 * Cached database functions
 */
function cached_query($sql, $params = [], $ttl = 600) {
    global $pdo;
    $query_cache = new QueryCache($pdo);
    return $query_cache->query($sql, $params, $ttl);
}

function cached_row($sql, $params = [], $ttl = 600) {
    global $pdo;
    $query_cache = new QueryCache($pdo);
    return $query_cache->queryRow($sql, $params, $ttl);
}

function cached_value($sql, $params = [], $ttl = 600) {
    global $pdo;
    $query_cache = new QueryCache($pdo);
    return $query_cache->queryValue($sql, $params, $ttl);
}

/**
 * Commonly cached queries
 */
function getCachedClasses($academic_year = null) {
    global $pdo;
    
    $academic_year = $academic_year ?? ($_SESSION['current_academic_year'] ?? '2024-2025');
    
    return cached_query(
        "SELECT * FROM classes WHERE is_active = 1 AND academic_year = ? ORDER BY class_name, section",
        [$academic_year],
        1800, // 30 minutes
        ["table_classes", "academic_year_{$academic_year}"]
    );
}

function getCachedStudentStats($academic_year = null) {
    global $pdo;
    
    $academic_year = $academic_year ?? ($_SESSION['current_academic_year'] ?? '2024-2025');
    
    return cached_row(
        "SELECT 
            COUNT(*) as total_students,
            COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_students,
            COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_students,
            COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
        FROM students 
        WHERE is_active = 1 AND academic_year = ?",
        [$academic_year],
        600, // 10 minutes
        ["table_students", "academic_year_{$academic_year}"]
    );
}

function getCachedFeeStats($academic_year = null) {
    global $pdo;
    
    $academic_year = $academic_year ?? ($_SESSION['current_academic_year'] ?? '2024-2025');
    
    return cached_row(
        "SELECT 
            COALESCE(SUM(amount), 0) as total_collected,
            COUNT(*) as total_transactions,
            COALESCE(SUM(CASE WHEN payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN amount END), 0) as monthly_collection
        FROM fee_payments 
        WHERE academic_year = ?",
        [$academic_year],
        300, // 5 minutes
        ["table_fee_payments", "academic_year_{$academic_year}"]
    );
}

/**
 * Cache warming functions
 */
function warmupCache() {
    $start_time = microtime(true);
    
    // Warm up common queries
    getCachedClasses();
    getCachedStudentStats();
    getCachedFeeStats();
    
    // Cache school settings
    ConfigCache::get('school_name');
    ConfigCache::get('academic_year_current');
    
    $execution_time = microtime(true) - $start_time;
    
    logInfo("Cache warmed up", [
        'execution_time' => $execution_time,
        'memory_usage' => memory_get_usage(true)
    ]);
}

/**
 * Automatic cache cleanup (run via cron or periodically)
 */
function cleanupCache() {
    $cache = CacheManager::getInstance();
    
    $deleted_entries = $cache->cleanup();
    $cache_stats = $cache->getStats();
    
    logInfo("Cache cleanup completed", [
        'deleted_entries' => $deleted_entries,
        'remaining_files' => $cache_stats['total_files'],
        'total_size_mb' => $cache_stats['total_size_mb']
    ]);
    
    return $deleted_entries;
}

// Initialize cache system
CacheManager::getInstance();

// Warm up cache on first load
if (!cache_get('cache_warmed_today')) {
    warmupCache();
    cache_set('cache_warmed_today', true, 86400); // 24 hours
}
?>
