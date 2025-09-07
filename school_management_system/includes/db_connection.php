<?php
/**
 * Enhanced Database Connection Management
 * 
 * Provides robust database connection with proper error handling,
 * logging, and graceful degradation
 */

// Include guard to prevent multiple inclusions
if (defined('DB_CONNECTION_LOADED')) {
    return;
}
define('DB_CONNECTION_LOADED', true);

require_once dirname(__DIR__) . '/includes/init.php';

// Database configuration constants - only define if not already defined
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'school_management');
}

// Global PDO instance
$pdo = null;

/**
 * Get database connection with error handling
 * 
 * @return PDO|null Database connection or null on failure
 */
function db_connect() {
    global $pdo;
    
    // Return existing connection if available
    if ($pdo instanceof PDO) {
        try {
            // Test connection with a simple query
            $pdo->query('SELECT 1');
            return $pdo;
        } catch (PDOException $e) {
            // Connection lost, recreate it
            $pdo = null;
        }
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 10
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Log successful connection (for debugging)
        error_log("Database connection established successfully");
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error with details
        $error_msg = "Database connection failed: " . $e->getMessage();
        error_log($error_msg);
        
        // Create logs directory if it doesn't exist
        $log_dir = dirname(__DIR__) . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Log to file
        $log_file = $log_dir . '/db_error.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] $error_msg\n", FILE_APPEND | LOCK_EX);
        
        return null;
    }
}

/**
 * Die with user-friendly error message
 * 
 * @param string $title Error title
 * @param string $message Error message
 */
function die_gracefully($title = 'Service Temporarily Unavailable', $message = null) {
    $default_message = 'We are experiencing technical difficulties. Please try again later or contact the administrator.';
    $message = $message ?: $default_message;
    
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .error-container {
                background: white;
                padding: 3rem;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
                margin: 20px;
            }
            .error-icon {
                font-size: 4rem;
                color: #e74c3c;
                margin-bottom: 1rem;
            }
            .error-title {
                color: #2c3e50;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            .error-message {
                color: #7f8c8d;
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .retry-btn {
                background: #3498db;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 5px;
                text-decoration: none;
                display: inline-block;
                cursor: pointer;
            }
            .retry-btn:hover {
                background: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title"><?php echo htmlspecialchars($title); ?></h1>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
            <a href="javascript:location.reload()" class="retry-btn">Try Again</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/**
 * Check database connection and die gracefully if failed
 * 
 * @return PDO Database connection
 */
function require_db_connection() {
    $connection = db_connect();
    
    if (!$connection) {
        die_gracefully(
            'Database Connection Error',
            'Unable to connect to the database. Please contact the system administrator if this problem persists.'
        );
    }
    
    return $connection;
}

// Initialize database connection
$pdo = require_db_connection();
?>
