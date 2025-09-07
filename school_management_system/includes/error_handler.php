<?php
/**
 * Comprehensive Error Handling and Logging System
 * 
 * Features:
 * - Multiple log levels (DEBUG, INFO, WARNING, ERROR, CRITICAL)
 * - Structured logging with context
 * - Error tracking and reporting
 * - Performance monitoring
 * - User-friendly error messages
 * - Email alerts for critical errors
 * - Log rotation and cleanup
 */

class ErrorHandler {
    
    const LOG_DEBUG = 'DEBUG';
    const LOG_INFO = 'INFO';
    const LOG_WARNING = 'WARNING';
    const LOG_ERROR = 'ERROR';
    const LOG_CRITICAL = 'CRITICAL';
    
    private static $instance = null;
    private $log_dir;
    private $max_log_size = 10485760; // 10MB
    private $max_log_files = 5;
    private $enable_email_alerts = false;
    private $admin_email = 'admin@school.com';
    
    public function __construct() {
        $this->log_dir = __DIR__ . '/../logs';
        $this->ensureLogDirectory();
        $this->setupErrorHandlers();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Setup PHP error handlers
     */
    private function setupErrorHandlers() {
        // Set custom error handler
        set_error_handler([$this, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Register shutdown function for fatal errors
        register_shutdown_function([$this, 'handleShutdown']);
        
        // Set error reporting level
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Don't display errors to users
        ini_set('log_errors', 1);
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($severity, $message, $file, $line) {
        // Don't handle suppressed errors
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $level = $this->getLogLevelFromSeverity($severity);
        $context = [
            'file' => $file,
            'line' => $line,
            'severity' => $severity,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $this->log($level, $message, $context);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $this->log(self::LOG_CRITICAL, $exception->getMessage(), $context);
        
        // Show user-friendly error page
        $this->showErrorPage('An unexpected error occurred. Please try again later.');
    }
    
    /**
     * Handle fatal errors during shutdown
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $context = [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type'],
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip_address' => $this->getClientIP()
            ];
            
            $this->log(self::LOG_CRITICAL, $error['message'], $context);
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->showErrorPage('A critical error occurred. Please contact support.');
        }
    }
    
    /**
     * Main logging function
     */
    public function log($level, $message, $context = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        // Write to appropriate log file
        $this->writeToLog($level, $log_entry);
        
        // Send email alert for critical errors
        if (in_array($level, [self::LOG_ERROR, self::LOG_CRITICAL]) && $this->enable_email_alerts) {
            $this->sendEmailAlert($level, $message, $context);
        }
        
        // Store in database if available
        $this->storeInDatabase($log_entry);
    }
    
    /**
     * Convenience logging methods
     */
    public function debug($message, $context = []) {
        $this->log(self::LOG_DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::LOG_INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::LOG_WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log(self::LOG_ERROR, $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log(self::LOG_CRITICAL, $message, $context);
    }
    
    /**
     * Log database queries for performance monitoring
     */
    public function logQuery($query, $params = [], $execution_time = 0) {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $execution_time,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $level = $execution_time > 1 ? self::LOG_WARNING : self::LOG_DEBUG;
        $message = "Database Query" . ($execution_time > 1 ? " (Slow Query: {$execution_time}s)" : "");
        
        $this->log($level, $message, $context);
    }
    
    /**
     * Log user activities
     */
    public function logActivity($user_id, $action, $details = [], $level = self::LOG_INFO) {
        $context = [
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ];
        
        $this->log($level, "User Activity: $action", $context);
    }
    
    /**
     * Log security events
     */
    public function logSecurity($event_type, $message, $context = []) {
        $security_context = array_merge($context, [
            'event_type' => $event_type,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id(),
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
        
        $this->log(self::LOG_WARNING, "Security Event: $message", $security_context);
        
        // Also write to security-specific log
        $this->writeToSecurityLog($event_type, $message, $security_context);
    }
    
    /**
     * Performance monitoring
     */
    public function logPerformance($operation, $start_time, $context = []) {
        $execution_time = microtime(true) - $start_time;
        
        $perf_context = array_merge($context, [
            'operation' => $operation,
            'execution_time' => $execution_time,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
        
        $level = $execution_time > 2 ? self::LOG_WARNING : self::LOG_DEBUG;
        $message = "Performance: $operation" . ($execution_time > 2 ? " (Slow: {$execution_time}s)" : "");
        
        $this->log($level, $message, $perf_context);
    }
    
    /**
     * Write log entry to file
     */
    private function writeToLog($level, $log_entry) {
        $log_file = $this->getLogFile($level);
        
        // Check file size and rotate if necessary
        if (file_exists($log_file) && filesize($log_file) > $this->max_log_size) {
            $this->rotateLogFile($log_file);
        }
        
        $formatted_entry = $this->formatLogEntry($log_entry);
        
        file_put_contents($log_file, $formatted_entry . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Write to security-specific log
     */
    private function writeToSecurityLog($event_type, $message, $context) {
        $security_log = $this->log_dir . '/security_' . date('Y-m') . '.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'message' => $message,
            'context' => $context
        ];
        
        file_put_contents($security_log, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Store log entry in database
     */
    private function storeInDatabase($log_entry) {
        try {
            global $pdo;
            
            if (!$pdo) return;
            
            // Only store ERROR and CRITICAL level logs in database
            if (!in_array($log_entry['level'], [self::LOG_ERROR, self::LOG_CRITICAL])) {
                return;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO error_logs (level, message, context, file, line, url, user_id, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $context = $log_entry['context'];
            $stmt->execute([
                $log_entry['level'],
                $log_entry['message'],
                json_encode($context),
                $context['file'] ?? null,
                $context['line'] ?? null,
                $context['url'] ?? null,
                $context['user_id'] ?? null,
                $context['ip_address'] ?? null,
                $log_entry['timestamp']
            ]);
            
        } catch (Exception $e) {
            // Silently fail if database is not available
            error_log("Failed to store error in database: " . $e->getMessage());
        }
    }
    
    /**
     * Format log entry for file output
     */
    private function formatLogEntry($log_entry) {
        $formatted = sprintf(
            "[%s] %s: %s",
            $log_entry['timestamp'],
            $log_entry['level'],
            $log_entry['message']
        );
        
        if (!empty($log_entry['context'])) {
            $formatted .= " | Context: " . json_encode($log_entry['context']);
        }
        
        return $formatted;
    }
    
    /**
     * Get appropriate log file for level
     */
    private function getLogFile($level) {
        $date = date('Y-m-d');
        
        switch ($level) {
            case self::LOG_DEBUG:
                return $this->log_dir . "/debug_$date.log";
            case self::LOG_INFO:
                return $this->log_dir . "/info_$date.log";
            case self::LOG_WARNING:
                return $this->log_dir . "/warning_$date.log";
            case self::LOG_ERROR:
            case self::LOG_CRITICAL:
                return $this->log_dir . "/error_$date.log";
            default:
                return $this->log_dir . "/general_$date.log";
        }
    }
    
    /**
     * Rotate log files when they get too large
     */
    private function rotateLogFile($log_file) {
        $backup_file = $log_file . '.1';
        
        // Rotate existing backups
        for ($i = $this->max_log_files - 1; $i >= 1; $i--) {
            $old_backup = $log_file . '.' . $i;
            $new_backup = $log_file . '.' . ($i + 1);
            
            if (file_exists($old_backup)) {
                if ($i == $this->max_log_files - 1) {
                    unlink($old_backup); // Delete oldest
                } else {
                    rename($old_backup, $new_backup);
                }
            }
        }
        
        // Move current log to backup
        if (file_exists($log_file)) {
            rename($log_file, $backup_file);
        }
    }
    
    /**
     * Clean up old log files
     */
    public function cleanupOldLogs($days = 30) {
        $files = glob($this->log_dir . '/*.log*');
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        $deleted_count = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Get log level from PHP error severity
     */
    private function getLogLevelFromSeverity($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                return self::LOG_ERROR;
                
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return self::LOG_WARNING;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                return self::LOG_INFO;
                
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return self::LOG_DEBUG;
                
            default:
                return self::LOG_INFO;
        }
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory() {
        if (!is_dir($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }
        
        // Create .htaccess to protect log files
        $htaccess_file = $this->log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Deny from all\n");
        }
    }
    
    /**
     * Send email alert for critical errors
     */
    private function sendEmailAlert($level, $message, $context) {
        if (!$this->enable_email_alerts || !in_array($level, [self::LOG_ERROR, self::LOG_CRITICAL])) {
            return;
        }
        
        $subject = "[$level] School Management System Error";
        $body = "An error occurred in the School Management System:\n\n";
        $body .= "Level: $level\n";
        $body .= "Message: $message\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $body .= "URL: " . ($context['url'] ?? 'N/A') . "\n";
        $body .= "User ID: " . ($context['user_id'] ?? 'N/A') . "\n";
        $body .= "IP Address: " . ($context['ip_address'] ?? 'N/A') . "\n\n";
        
        if (isset($context['file']) && isset($context['line'])) {
            $body .= "File: {$context['file']}:{$context['line']}\n\n";
        }
        
        if (isset($context['trace'])) {
            $body .= "Stack Trace:\n{$context['trace']}\n";
        }
        
        // Use PHP's mail function or configure with proper SMTP
        @mail($this->admin_email, $subject, $body, [
            'From' => 'noreply@school.com',
            'Content-Type' => 'text/plain; charset=UTF-8'
        ]);
    }
    
    /**
     * Show user-friendly error page
     */
    private function showErrorPage($message) {
        // Only show error page if we haven't sent any output yet
        if (!headers_sent()) {
            http_response_code(500);
            
            // Check if it's an AJAX request
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => true,
                    'message' => $message,
                    'code' => 500
                ]);
            } else {
                // Show HTML error page
                include __DIR__ . '/error_page.php';
            }
        }
        
        exit;
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                    'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get recent errors for admin dashboard
     */
    public function getRecentErrors($limit = 50, $level = null) {
        try {
            global $pdo;
            
            if (!$pdo) return [];
            
            $sql = "SELECT * FROM error_logs WHERE 1=1";
            $params = [];
            
            if ($level) {
                $sql .= " AND level = ?";
                $params[] = $level;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get error statistics
     */
    public function getErrorStats($days = 7) {
        try {
            global $pdo;
            
            if (!$pdo) return [];
            
            $stmt = $pdo->prepare("
                SELECT 
                    level,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM error_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY level, DATE(created_at)
                ORDER BY date DESC, level
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
}

/**
 * Database query wrapper with logging
 */
class LoggedPDO extends PDO {
    
    private $error_handler;
    
    public function __construct($dsn, $username, $password, $options = []) {
        $this->error_handler = ErrorHandler::getInstance();
        
        try {
            parent::__construct($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $this->error_handler->critical("Database connection failed", [
                'dsn' => $dsn,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function prepare($statement, $driver_options = []) {
        $start_time = microtime(true);
        
        try {
            $stmt = parent::prepare($statement, $driver_options);
            
            if (!$stmt) {
                $this->error_handler->error("Failed to prepare statement", [
                    'statement' => $statement,
                    'error_info' => $this->errorInfo()
                ]);
            }
            
            return new LoggedPDOStatement($stmt, $this->error_handler, $statement, $start_time);
            
        } catch (PDOException $e) {
            $this->error_handler->error("Database prepare error", [
                'statement' => $statement,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function exec($statement) {
        $start_time = microtime(true);
        
        try {
            $result = parent::exec($statement);
            
            $execution_time = microtime(true) - $start_time;
            $this->error_handler->logQuery($statement, [], $execution_time);
            
            return $result;
            
        } catch (PDOException $e) {
            $this->error_handler->error("Database exec error", [
                'statement' => $statement,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

/**
 * Logged PDO Statement wrapper
 */
class LoggedPDOStatement {
    
    private $stmt;
    private $error_handler;
    private $query;
    private $start_time;
    
    public function __construct($stmt, $error_handler, $query, $start_time) {
        $this->stmt = $stmt;
        $this->error_handler = $error_handler;
        $this->query = $query;
        $this->start_time = $start_time;
    }
    
    public function execute($input_parameters = null) {
        try {
            $result = $this->stmt->execute($input_parameters);
            
            $execution_time = microtime(true) - $this->start_time;
            $this->error_handler->logQuery($this->query, $input_parameters ?? [], $execution_time);
            
            return $result;
            
        } catch (PDOException $e) {
            $this->error_handler->error("Database execution error", [
                'query' => $this->query,
                'params' => $input_parameters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    // Delegate all other method calls to the original statement
    public function __call($method, $args) {
        return call_user_func_array([$this->stmt, $method], $args);
    }
    
    public function __get($property) {
        return $this->stmt->$property;
    }
}

/**
 * Global helper functions
 */
function logError($message, $context = []) {
    ErrorHandler::getInstance()->error($message, $context);
}

function logWarning($message, $context = []) {
    ErrorHandler::getInstance()->warning($message, $context);
}

function logInfo($message, $context = []) {
    ErrorHandler::getInstance()->info($message, $context);
}

function logDebug($message, $context = []) {
    ErrorHandler::getInstance()->debug($message, $context);
}

function logSecurity($event_type, $message, $context = []) {
    ErrorHandler::getInstance()->logSecurity($event_type, $message, $context);
}

function logActivity($user_id, $action, $details = []) {
    ErrorHandler::getInstance()->logActivity($user_id, $action, $details);
}

function logPerformance($operation, $start_time, $context = []) {
    ErrorHandler::getInstance()->logPerformance($operation, $start_time, $context);
}

/**
 * Performance monitoring helper
 */
function startPerformanceTimer() {
    return microtime(true);
}

function endPerformanceTimer($operation, $start_time, $context = []) {
    logPerformance($operation, $start_time, $context);
}

/**
 * Database error logging function
 */
function handleDatabaseError($e, $query = '', $params = []) {
    ErrorHandler::getInstance()->error("Database Error: " . $e->getMessage(), [
        'query' => $query,
        'params' => $params,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * User-friendly error display
 */
function displayUserError($message, $type = 'error') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Initialize error handler
 */
ErrorHandler::getInstance();

// Create error_logs table if it doesn't exist
try {
    global $pdo;
    if ($pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS error_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                level VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                context JSON,
                file VARCHAR(500),
                line INT,
                url VARCHAR(500),
                user_id INT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_level_date (level, created_at),
                INDEX idx_user_errors (user_id, created_at),
                INDEX idx_file_errors (file, line)
            )
        ");
    }
} catch (Exception $e) {
    // Silently fail if database is not available
}
?>
