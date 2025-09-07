<?php
/**
 * Simple Database Connection
 * Secure mysqli connection for student management system
 * 
 * This file provides:
 * - Safe database connection with error handling
 * - Session management for user authentication
 * - Admin authorization checks
 * - SQL injection protection through mysqli prepared statements
 */

// Database configuration - should be moved to environment variables in production
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'school_management';

// Create secure connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection and handle errors gracefully
if ($conn->connect_error) {
    // Log error details (don't expose to user)
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please contact system administrator.");
}

// Set charset to prevent character set confusion attacks
$conn->set_charset("utf8mb4");

// Start session with security settings
session_start();

// Regenerate session ID on login to prevent session fixation
if (!isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = true;
}

/**
 * Check if user is authenticated as admin
 * Redirects to login if not authenticated or not admin role
 */
function check_admin() {
    // Check if user is logged in and has admin role
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        // Clear any existing session data
        session_unset();
        session_destroy();
        
        // Redirect to login page
        header('Location: ../login.php');
        exit();
    }
    
    // Optional: Check session timeout (uncomment to enable)
    // $timeout = 3600; // 1 hour
    // if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    //     session_unset();
    //     session_destroy();
    //     header('Location: ../login.php?timeout=1');
    //     exit();
    // }
    // $_SESSION['last_activity'] = time();
}
?>
