<?php

/**
 * Security Helper Functions
 * Provides CSRF protection, input validation, rate limiting, and other security features
 */

// CSRF Token Functions
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf() {
    if (!verify_csrf()) {
        http_response_code(403);
        die('CSRF token mismatch. Please refresh the page and try again.');
    }
}

// Input Validation Class
class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function required($field, $message = null) {
        if (empty($this->data[$field])) {
            $this->errors[$field] = $message ?? "The {$field} field is required.";
        }
        return $this;
    }
    
    public function email($field, $message = null) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "The {$field} must be a valid email address.";
        }
        return $this;
    }
    
    public function min($field, $min, $message = null) {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "The {$field} must be at least {$min} characters.";
        }
        return $this;
    }
    
    public function max($field, $max, $message = null) {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "The {$field} may not be greater than {$max} characters.";
        }
        return $this;
    }
    
    public function numeric($field, $message = null) {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "The {$field} must be a number.";
        }
        return $this;
    }
    
    public function date($field, $message = null) {
        if (!empty($this->data[$field])) {
            $date = DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if (!$date || $date->format('Y-m-d') !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "The {$field} must be a valid date.";
            }
        }
        return $this;
    }
    
    public function unique($field, $table, $column = null, $exclude_id = null, $message = null) {
        global $pdo;
        
        $column = $column ?? $field;
        
        if (!empty($this->data[$field])) {
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$this->data[$field]];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() > 0) {
                $this->errors[$field] = $message ?? "The {$field} has already been taken.";
            }
        }
        return $this;
    }
    
    public function in($field, $values, $message = null) {
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "The selected {$field} is invalid.";
        }
        return $this;
    }
    
    public function phone($field, $message = null) {
        if (!empty($this->data[$field])) {
            $phone = preg_replace('/[^0-9]/', '', $this->data[$field]);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $this->errors[$field] = $message ?? "The {$field} must be a valid phone number.";
            }
        }
        return $this;
    }
    
    public function fails() {
        return !empty($this->errors);
    }
    
    public function errors() {
        return $this->errors;
    }
    
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }
    
    public function validated() {
        if ($this->fails()) {
            return false;
        }
        
        $validated = [];
        foreach (array_keys($this->errors) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        
        return $validated;
    }
}

// Rate Limiting Functions
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 300) {
    $cache_file = __DIR__ . '/../tmp/rate_limit_' . md5($identifier) . '.json';
    
    // Create tmp directory if it doesn't exist
    $tmp_dir = dirname($cache_file);
    if (!is_dir($tmp_dir)) {
        mkdir($tmp_dir, 0755, true);
    }
    
    $now = time();
    $attempts = [];
    
    // Load existing attempts
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && is_array($data)) {
            // Filter out old attempts
            $attempts = array_filter($data, function($timestamp) use ($now, $time_window) {
                return ($now - $timestamp) < $time_window;
            });
        }
    }
    
    // Check if rate limit exceeded
    if (count($attempts) >= $max_attempts) {
        return false;
    }
    
    // Add current attempt
    $attempts[] = $now;
    file_put_contents($cache_file, json_encode($attempts));
    
    return true;
}

function get_client_ip() {
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

// Activity Logging
function log_activity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    global $pdo, $user_id;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $table_name,
            $record_id,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

// File Upload Security
function validate_file_upload($file, $allowed_types = null, $max_size = null) {
    global $pdo;
    
    // Get allowed file types and max size from settings if not provided
    if ($allowed_types === null) {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'allowed_file_types'");
        $stmt->execute();
        $setting = $stmt->fetch();
        $allowed_types = $setting ? json_decode($setting['setting_value'], true) : ['pdf', 'jpg', 'jpeg', 'png'];
    }
    
    if ($max_size === null) {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'max_file_upload_size'");
        $stmt->execute();
        $setting = $stmt->fetch();
        $max_size = $setting ? (int)$setting['setting_value'] : 5242880; // 5MB default
    }
    
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed with error code: ' . $file['error'];
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds maximum allowed size of ' . number_format($max_size / 1024 / 1024, 1) . 'MB';
    }
    
    // Check file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (isset($allowed_mime_types[$file_extension]) && $mime_type !== $allowed_mime_types[$file_extension]) {
        $errors[] = 'File type mismatch. Expected ' . $allowed_mime_types[$file_extension] . ' but got ' . $mime_type;
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'file_extension' => $file_extension,
        'mime_type' => $mime_type
    ];
}

// Generate secure filename
function generate_secure_filename($original_filename, $prefix = '') {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $safe_name = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    return $prefix . $safe_name . '_' . $timestamp . '_' . $random . '.' . $extension;
}

// XSS Protection
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

// Safe redirect function
function safe_redirect($url, $fallback = '/') {
    // Only allow relative URLs or URLs to the same domain
    $parsed_url = parse_url($url);
    
    if (isset($parsed_url['host']) && $parsed_url['host'] !== $_SERVER['HTTP_HOST']) {
        $url = $fallback;
    }
    
    header('Location: ' . $url);
    exit;
}

// Password strength validation
function validate_password_strength($password, $min_length = 8) {
    $errors = [];
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password must be at least {$min_length} characters long";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'strength' => calculate_password_strength($password)
    ];
}

function calculate_password_strength($password) {
    $score = 0;
    
    // Length score
    $score += min(strlen($password), 12) * 2;
    
    // Character variety
    if (preg_match('/[a-z]/', $password)) $score += 5;
    if (preg_match('/[A-Z]/', $password)) $score += 5;
    if (preg_match('/[0-9]/', $password)) $score += 5;
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 10;
    
    // Bonus for length
    if (strlen($password) >= 12) $score += 10;
    
    // Return strength level
    if ($score < 30) return 'weak';
    if ($score < 60) return 'medium';
    if ($score < 90) return 'strong';
    return 'very_strong';
}

// Secure session management
function regenerate_session_id() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
    }
}

// Check session timeout
function check_session_timeout() {
    global $pdo;
    
    // Get session timeout from settings
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'session_timeout_minutes'");
    $stmt->execute();
    $setting = $stmt->fetch();
    $timeout_minutes = $setting ? (int)$setting['setting_value'] : 60;
    
    $timeout_seconds = $timeout_minutes * 60;
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_seconds) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// SQL Injection Protection (helper for dynamic queries)
function escape_like_string($string) {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $string);
}

// Remove potentially dangerous characters from filenames
function sanitize_filename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Remove potentially dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Prevent hidden files
    if (strpos($filename, '.') === 0) {
        $filename = 'file_' . $filename;
    }
    
    return $filename;
}

// Check if file type is safe
function is_safe_file_type($filename) {
    $dangerous_extensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'scr', 'vbs', 'js', 'jar'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    return !in_array($extension, $dangerous_extensions);
}

// Generate secure random strings
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Hash sensitive data
function hash_sensitive_data($data, $salt = null) {
    $salt = $salt ?? generate_random_string(32);
    return hash('sha256', $data . $salt);
}

// Content Security Policy headers
function set_security_headers() {
    // Prevent XSS
    header("X-XSS-Protection: 1; mode=block");
    
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Prevent clickjacking
    header("X-Frame-Options: DENY");
    
    // Strict transport security (HTTPS only)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self';");
    
    // Referrer policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Centralized Authentication Functions
 * Provides unified authentication checks for all user roles
 */

/**
 * Centralized authentication check
 * @param string $required_role The role required to access the page (admin, teacher, cashier, student)
 * @param bool $redirect_on_fail Whether to redirect on authentication failure (default: true)
 * @param string $redirect_url URL to redirect to on failure (default: ../login.php)
 * @return bool Returns true if authenticated, false otherwise
 */
function requireAuth($required_role = null, $redirect_on_fail = true, $redirect_url = '../login.php') {
    // Start session if not already started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        if ($redirect_on_fail) {
            header('Location: ' . $redirect_url);
            exit();
        }
        return false;
    }
    
    // Check session timeout
    if (!check_session_timeout()) {
        if ($redirect_on_fail) {
            header('Location: ' . $redirect_url . '?timeout=1');
            exit();
        }
        return false;
    }
    
    // Check if specific role is required
    if ($required_role && $_SESSION['user_role'] !== $required_role) {
        if ($redirect_on_fail) {
            header('Location: ' . get_dashboard_url($_SESSION['user_role']));
            exit();
        }
        return false;
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['regenerated']) || (time() - $_SESSION['regenerated']) > 300) {
        regenerate_session_id();
    }
    
    return true;
}

/**
 * Get dashboard URL based on user role
 * @param string $role User role
 * @return string Dashboard URL for the role
 */
function get_dashboard_url($role) {
    switch ($role) {
        case 'admin':
            return '../admin/enhanced_dashboard.php';
        case 'teacher':
            return '../teacher/dashboard.php';
        case 'cashier':
            return '../cashier/dashboard.php';
        case 'student':
            return '../student/dashboard.php';
        default:
            return '../login.php';
    }
}

/**
 * Check if current user has a specific role
 * @param string $role Role to check
 * @return bool True if user has the role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if current user can access a resource
 * @param string|array $allowed_roles Single role or array of allowed roles
 * @return bool True if user can access
 */
function canAccess($allowed_roles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    return in_array($_SESSION['user_role'], $allowed_roles);
}

/**
 * Get current user information
 * @return array|null User information or null if not logged in
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Unknown',
        'role' => $_SESSION['user_role'] ?? 'guest',
        'email' => $_SESSION['user_email'] ?? null
    ];
}

/**
 * Log user activity with automatic user ID
 * @param string $action Action performed
 * @param string $table_name Table affected (optional)
 * @param int $record_id Record ID affected (optional)
 * @param array $old_values Old values (optional)
 * @param array $new_values New values (optional)
 * @return bool Success status
 */
function logUserActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    return log_activity($action, $table_name, $record_id, $old_values, $new_values);
}

// Call security headers by default
set_security_headers();
?>
