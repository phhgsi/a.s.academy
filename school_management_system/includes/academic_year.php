<?php
// Academic Year Management System
// Handles academic year selection and filtering across the application

// Use the enhanced database connection
require_once __DIR__ . '/db_connection.php';

// Handle academic year change FIRST (before any output)
if (isset($_POST['change_academic_year']) && isset($_POST['academic_year'])) {
    $new_academic_year = $_POST['academic_year'];
    
    // Validate academic year format (YYYY-YYYY)
    if (preg_match('/^\d{4}-\d{4}$/', $new_academic_year)) {
        $_SESSION['current_academic_year'] = $new_academic_year;
        
        // Update in database
        try {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES ('current_academic_year', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->execute([$new_academic_year, $new_academic_year]);
            
            // Redirect to refresh the page with new academic year
            $redirect_url = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];
            $redirect_url = preg_replace('/[?&]academic_year_changed=1/', '', $redirect_url);
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'academic_year_changed=1';
            
            header('Location: ' . $redirect_url);
            exit();
        } catch (Exception $e) {
            $academic_year_error = 'Error updating academic year: ' . $e->getMessage();
        }
    } else {
        $academic_year_error = 'Invalid academic year format.';
    }
}

// Initialize academic year in session if not set
if (!isset($_SESSION['current_academic_year'])) {
    // Get the current academic year from settings or set default
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'current_academic_year'");
        $stmt->execute();
        $setting = $stmt->fetch();
        
        if ($setting) {
            $_SESSION['current_academic_year'] = $setting['setting_value'];
        } else {
            throw new Exception('No setting found');
        }
    } catch (Exception $e) {
        // Generate current academic year (April to March) if table doesn't exist or setting not found
        $current_month = date('n');
        $current_year = date('Y');
        
        if ($current_month >= 4) {
            // April onwards - current year to next year
            $_SESSION['current_academic_year'] = $current_year . '-' . ($current_year + 1);
        } else {
            // January to March - previous year to current year
            $_SESSION['current_academic_year'] = ($current_year - 1) . '-' . $current_year;
        }
        
        // Try to save to database if table exists
        try {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES ('current_academic_year', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->execute([$_SESSION['current_academic_year'], $_SESSION['current_academic_year']]);
        } catch (Exception $db_e) {
            // Ignore if system_settings table doesn't exist yet
        }
    }
}


// Get available academic years from the database
function getAvailableAcademicYears($pdo) {
    $years = [];
    
    // Get years from existing data
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT academic_year 
            FROM (
                SELECT academic_year FROM students WHERE academic_year IS NOT NULL
                UNION
                SELECT academic_year FROM academic_years WHERE is_active = 1
                UNION 
                SELECT academic_year FROM fee_structure WHERE academic_year IS NOT NULL
            ) AS all_years 
            ORDER BY academic_year DESC
        ");
        $stmt->execute();
        $db_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($db_years)) {
            $years = $db_years;
        }
    } catch (Exception $e) {
        // If tables don't exist or have issues, fall back to generated years
    }
    
    // If no years found in database, generate some default years
    if (empty($years)) {
        $current_year = date('Y');
        $current_month = date('n');
        
        // Generate 5 years range
        for ($i = -2; $i <= 2; $i++) {
            $year = $current_year + $i;
            $years[] = $year . '-' . ($year + 1);
        }
    }
    
    return $years;
}

// Get current academic year
function getCurrentAcademicYear() {
    // Check session first
    if (!empty($_SESSION['current_academic_year'])) {
        return $_SESSION['current_academic_year'];
    }
    
    // Try to get from database
    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'current_academic_year'");
            $stmt->execute();
            $setting = $stmt->fetch();
            
            if ($setting && !empty($setting['setting_value'])) {
                $_SESSION['current_academic_year'] = $setting['setting_value'];
                return $setting['setting_value'];
            }
        } catch (Exception $e) {
            // Database error, continue to fallback
            error_log('Error fetching academic year from database: ' . $e->getMessage());
        }
    }
    
    // Generate fallback based on current date (April to March academic year)
    $current_month = date('n');
    $current_year = date('Y');
    
    if ($current_month >= 4) {
        // April onwards - current year to next year
        $academic_year = $current_year . '-' . ($current_year + 1);
    } else {
        // January to March - previous year to current year
        $academic_year = ($current_year - 1) . '-' . $current_year;
    }
    
    // Cache in session
    $_SESSION['current_academic_year'] = $academic_year;
    
    // Try to save to database for future use
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES ('current_academic_year', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->execute([$academic_year, $academic_year]);
        } catch (Exception $e) {
            // Ignore database errors during fallback
            error_log('Error saving fallback academic year: ' . $e->getMessage());
        }
    }
    
    return $academic_year;
}

// Set academic year
function setCurrentAcademicYear($academic_year) {
    global $pdo;
    
    if (preg_match('/^\d{4}-\d{4}$/', $academic_year)) {
        $_SESSION['current_academic_year'] = $academic_year;
        
        // Update in database
        try {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES ('current_academic_year', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
            $stmt->execute([$academic_year, $academic_year]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    return false;
}

// Add academic year filter to SQL WHERE clause
function addAcademicYearFilter($table_alias = '') {
    $academic_year = getCurrentAcademicYear();
    if (!$academic_year) return '';
    
    $prefix = $table_alias ? $table_alias . '.' : '';
    return " AND {$prefix}academic_year = " . $GLOBALS['pdo']->quote($academic_year);
}

// Get academic year dropdown HTML
function getAcademicYearDropdown($name = 'academic_year', $selected = null, $required = true) {
    global $pdo;
    
    $selected = $selected ?? getCurrentAcademicYear();
    $years = getAvailableAcademicYears($pdo);
    
    $html = '<select name="' . $name . '" class="form-select"' . ($required ? ' required' : '') . '>';
    $html .= '<option value="">Select Academic Year</option>';
    
    foreach ($years as $year) {
        $isSelected = ($year === $selected) ? 'selected' : '';
        $html .= '<option value="' . $year . '" ' . $isSelected . '>' . $year . '</option>';
    }
    
    $html .= '</select>';
    return $html;
}

// Academic year selector widget for header
function renderAcademicYearSelector() {
    global $pdo;
    
    $current_year = getCurrentAcademicYear();
    $available_years = getAvailableAcademicYears($pdo);
    
    echo '<div class="academic-year-selector">';
    echo '<form method="POST" onsubmit="showLoadingSpinner()" style="margin: 0;">';
    echo '<label>Academic Year:</label>';
    echo '<select name="academic_year" onchange="this.form.submit()">';
    
    foreach ($available_years as $year) {
        $selected = ($year === $current_year) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($year) . '" ' . $selected . '>' . htmlspecialchars($year) . '</option>';
    }
    
    echo '</select>';
    echo '<input type="hidden" name="change_academic_year" value="1">';
    echo '</form>';
    echo '</div>';
}

// Check if academic year exists in academic_years table
function ensureAcademicYearExists($academic_year) {
    global $pdo;
    
    if (!$academic_year) return;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM academic_years WHERE academic_year = ?");
        $stmt->execute([$academic_year]);
        
        if ($stmt->fetchColumn() == 0) {
            // Create academic year record
            $parts = explode('-', $academic_year);
            if (count($parts) == 2) {
                $start_year = $parts[0];
                $end_year = $parts[1];
                
                $stmt = $pdo->prepare("
                    INSERT INTO academic_years (year_name, academic_year, start_date, end_date, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([
                    $academic_year,           // year_name
                    $academic_year,           // academic_year
                    $start_year . '-04-01',   // April 1st start
                    $end_year . '-03-31'      // March 31st end
                ]);
            }
        }
    } catch (Exception $e) {
        // Ignore if academic_years table doesn't exist or other DB issues
    }
}

// Initialize academic year
$current_academic_year = getCurrentAcademicYear();
ensureAcademicYearExists($current_academic_year);
?>
