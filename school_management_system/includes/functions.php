<?php
// Helper Functions for School Management System

// Get base URL for the application
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname(dirname($script)); // Go up two levels from current script
    
    // Clean up the path
    $path = str_replace('\\', '/', $path);
    $path = rtrim($path, '/');
    
    return $protocol . $host . $path;
}

// Flash message functions
function setFlash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

function getFlash() {
    if (!isset($_SESSION['flash'])) {
        return [];
    }
    
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function hasFlash() {
    return isset($_SESSION['flash']) && !empty($_SESSION['flash']);
}

// Activity icon helper
function getActivityIcon($action) {
    $icons = [
        'create' => 'plus-circle',
        'update' => 'pencil-square', 
        'delete' => 'trash',
        'login' => 'box-arrow-in-right',
        'logout' => 'box-arrow-right',
        'payment' => 'currency-rupee'
    ];
    return $icons[$action] ?? 'activity';
}

// Notification icon helper
function getNotificationIcon($type) {
    $icons = [
        'info' => 'info-circle',
        'warning' => 'exclamation-triangle',
        'success' => 'check-circle',
        'error' => 'x-circle',
        'reminder' => 'bell'
    ];
    return $icons[$type] ?? 'bell';
}

// Notification color helper
function getNotificationColor($type) {
    $colors = [
        'info' => 'info',
        'warning' => 'warning',
        'success' => 'success', 
        'error' => 'danger',
        'reminder' => 'primary'
    ];
    return $colors[$type] ?? 'info';
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log activity
function logActivity($user_id, $action, $description, $table_name = null, $record_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, description, table_name, record_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $description, $table_name, $record_id]);
    } catch (Exception $e) {
        // Silently fail if activity_log table doesn't exist
    }
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check if user has permission
function hasPermission($required_role) {
    $user_role = $_SESSION['user_role'] ?? 'guest';
    
    $role_hierarchy = [
        'guest' => 0,
        'student' => 1,
        'teacher' => 2,
        'cashier' => 3,
        'admin' => 4
    ];
    
    $user_level = $role_hierarchy[$user_role] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 4;
    
    return $user_level >= $required_level;
}
?>
