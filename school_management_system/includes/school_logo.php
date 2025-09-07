<?php
/**
 * School Logo Helper
 * 
 * Provides dynamic school logo functionality that pulls from the database
 * and provides fallback options for consistent display across the system.
 */

// Ensure we have a database connection
if (!isset($pdo) || !$pdo) {
    // Try to get connection from global scope or create one
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        // We have mysqli connection, we can work with that
        $use_mysqli = true;
    } else {
        // No database connection available, use defaults
        $use_mysqli = false;
    }
} else {
    $use_mysqli = false;
}

// Cache logo info in session for performance
if (!isset($_SESSION['school_logo_cache']) || (time() - ($_SESSION['school_logo_cache_time'] ?? 0)) > 300) {
    try {
        if ($use_mysqli && isset($conn)) {
            // Use mysqli connection
            $result = $conn->query("SELECT school_name, logo FROM school_info WHERE id = 1");
            $school_info = $result ? $result->fetch_assoc() : null;
        } elseif (isset($pdo) && $pdo) {
            // Use PDO connection
            $stmt = $pdo->prepare("SELECT school_name, logo FROM school_info WHERE id = 1");
            $stmt->execute();
            $school_info = $stmt->fetch();
        } else {
            // No database connection, use defaults
            $school_info = null;
        }
        
        $_SESSION['school_logo_cache'] = $school_info;
        $_SESSION['school_logo_cache_time'] = time();
    } catch (Exception $e) {
        $_SESSION['school_logo_cache'] = null;
    }
}

$cached_school_info = $_SESSION['school_logo_cache'];

/**
 * Get school logo HTML with dynamic sizing and fallback
 * 
 * @param string $size Size variant: 'sm' (24px), 'md' (36px), 'lg' (48px), 'xl' (64px)
 * @param string $class Additional CSS classes
 * @return string HTML string with img tag or fallback
 */
function getSchoolLogo($size = 'sm', $class = '') {
    global $cached_school_info;
    
    $sizes = [
        'sm' => 24,
        'md' => 36, 
        'lg' => 48,
        'xl' => 64
    ];
    
    $pixel_size = $sizes[$size] ?? $sizes['sm'];
    $school_name = $cached_school_info['school_name'] ?? 'School';
    $logo_file = $cached_school_info['logo'] ?? '';
    
    // Check if we're in admin directory or root
    $uploads_path = file_exists('uploads/') ? 'uploads/' : '../uploads/';
    $logo_path = $uploads_path . $logo_file;
    
    $base_class = "school-logo logo-{$size}";
    $full_class = trim("{$base_class} {$class}");
    
    if ($logo_file && file_exists($logo_path)) {
        // Add cache busting parameter for immediate updates
        $cache_buster = filemtime($logo_path);
        $logo_url = $logo_path . '?v=' . $cache_buster;
        return "<img src=\"{$logo_url}\" alt=\"{$school_name} Logo\" class=\"{$full_class}\" style=\"width: {$pixel_size}px; height: {$pixel_size}px; object-fit: contain; border-radius: 8px;\">";
    } else {
        // Fallback to school initials
        $initials = strtoupper(substr($school_name, 0, 2));
        $font_size = round($pixel_size * 0.4);
        
        return "<div class=\"school-logo-fallback {$full_class}\" style=\"
            width: {$pixel_size}px; 
            height: {$pixel_size}px; 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: {$font_size}px; 
            font-weight: 700; 
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        \">{$initials}</div>";
    }
}

/**
 * Get school name for display
 */
function getSchoolName() {
    global $cached_school_info;
    return $cached_school_info['school_name'] ?? 'A.S.ACADEMY';
}

/**
 * Clear school logo cache (call after updating school info)
 */
function clearSchoolLogoCache() {
    unset($_SESSION['school_logo_cache']);
    unset($_SESSION['school_logo_cache_time']);
}
?>
