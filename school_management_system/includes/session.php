<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security settings for sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not on login page, redirect to login
    if (basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'index.php') {
        header('Location: ' . getBaseUrl() . '/login.php');
        exit;
    }
}

// Set global variables for easy access
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_email = $_SESSION['user_email'] ?? '';

// Regenerate session ID periodically for security
if (!isset($_SESSION['regenerated']) || time() - $_SESSION['regenerated'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = time();
}

// Function to get base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove specific module folders from path
    $script_path = preg_replace('/\/(admin|teacher|student|cashier)$/', '', $script_path);
    
    return $protocol . '://' . $host . $script_path;
}

// Function to check if user has required role
function checkRole($required_role) {
    global $user_role;
    if ($user_role !== $required_role) {
        header('Location: ' . getBaseUrl() . '/login.php?error=unauthorized');
        exit;
    }
}

// Function to check if user has any of the required roles
function checkRoles($required_roles) {
    global $user_role;
    if (!in_array($user_role, $required_roles)) {
        header('Location: ' . getBaseUrl() . '/login.php?error=unauthorized');
        exit;
    }
}

// Function to logout user
function logout() {
    session_destroy();
    header('Location: ' . getBaseUrl() . '/login.php?message=logged_out');
    exit;
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Get school information for global use
try {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT * FROM school_info LIMIT 1");
    $stmt->execute();
    $school_info = $stmt->fetch();
    
    // Set defaults if no school info found
    if (!$school_info) {
        $school_info = [
            'school_name' => 'School Management System',
            'school_code' => 'SMS',
            'address' => '',
            'phone' => '',
            'email' => '',
            'principal_name' => '',
            'established_year' => date('Y'),
            'logo' => ''
        ];
    }
} catch (Exception $e) {
    // Default school info if database error
    $school_info = [
        'school_name' => 'School Management System',
        'school_code' => 'SMS',
        'address' => '',
        'phone' => '',
        'email' => '',
        'principal_name' => '',
        'established_year' => date('Y'),
        'logo' => ''
    ];
}

// Flash message system
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type = null) {
    if ($type) {
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    
    $messages = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $messages;
}

function hasFlash($type = null) {
    if ($type) {
        return isset($_SESSION['flash'][$type]);
    }
    return !empty($_SESSION['flash']);
}
?>
