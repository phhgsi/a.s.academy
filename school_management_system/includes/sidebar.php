<?php
/**
 * Modern Categorized Sidebar Navigation
 * 
 * @description Generates a role-based categorized sidebar menu with:
 *   - Dynamic menu structure based on user role
 *   - Semantic HTML with proper ARIA attributes
 *   - Bootstrap Icons integration
 *   - Collapsible submenu support
 *   - Active state management
 * 
 * @version 1.0.0
 * @author School Management System Team
 * @requires Bootstrap Icons v1.11.0
 * @requires modern-ui.css
 * @requires sidebar.js
 * 
 * @usage Include this file in your page layout:
 *   <?php include '../includes/sidebar.php'; ?>
 * 
 * @dependencies
 *   - $_SESSION['user_role'] - Current user role (admin/teacher/cashier/student)
 *   - $_SESSION['user_name'] - Current user name for display
 *   - Bootstrap Icons CDN
 */

// Include school logo helper
require_once dirname(__FILE__) . '/school_logo.php';

// Ensure session variables are available
if (!isset($user_role)) {
    $user_role = $_SESSION['user_role'] ?? 'guest';
}
if (!isset($user_name)) {
    $user_name = $_SESSION['user_name'] ?? 'Guest User';
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'] ?? '';
$base_path = '';
if (strpos($current_path, '/admin/') !== false) {
    $base_path = '';
} else {
    $base_path = 'admin/';
}

// Define modern categorized menu structure
function getMenuCategories($user_role, $base_path) {
    $menu_categories = [];
    
    switch ($user_role) {
        case 'admin':
            $menu_categories = [
                'Dashboard' => [
                    ['icon' => 'speedometer2', 'title' => 'Dashboard', 'url' => $base_path . 'dashboard.php']
                ],
                'Academic Management' => [
                    ['icon' => 'people', 'title' => 'Students List', 'url' => $base_path . 'students_list.php'],
                    ['icon' => 'person-plus', 'title' => 'Add Student', 'url' => $base_path . 'students_add.php'],
                    ['icon' => 'person-badge', 'title' => 'Teachers', 'url' => $base_path . 'teachers.php'],
                    ['icon' => 'building', 'title' => 'Classes', 'url' => $base_path . 'classes.php'],
                    ['icon' => 'book', 'title' => 'Subjects', 'url' => $base_path . 'subjects.php'],
                    ['icon' => 'calendar-check', 'title' => 'Attendance', 'url' => $base_path . 'attendance.php'],
                    ['icon' => 'calendar3', 'title' => 'Academic Years', 'url' => $base_path . 'academic_years.php']
                ],
                'Financial Management' => [
                    ['icon' => 'currency-rupee', 'title' => 'Fee Payments', 'url' => $base_path . 'fees_list.php'],
                    ['icon' => 'cash-coin', 'title' => 'Collect Fee', 'url' => $base_path . 'fees_collect.php'],
                    ['icon' => 'table', 'title' => 'Fee Structure', 'url' => $base_path . 'fees_structure.php'],
                    ['icon' => 'receipt', 'title' => 'Expenses List', 'url' => $base_path . 'expenses_list.php'],
                    ['icon' => 'plus-circle', 'title' => 'Add Expense', 'url' => $base_path . 'expenses_add.php'],
                    ['icon' => 'graph-up', 'title' => 'Reports', 'url' => $base_path . 'reports.php']
                ],
                'Communication' => [
                    ['icon' => 'chat-dots', 'title' => 'Messages', 'url' => $base_path . 'messages.php'],
                    ['icon' => 'images', 'title' => 'Gallery', 'url' => $base_path . 'gallery.php'],
                    ['icon' => 'newspaper', 'title' => 'News & Events', 'url' => $base_path . 'news.php']
                ],
                'System Management' => [
                    ['icon' => 'building-gear', 'title' => 'School Info', 'url' => $base_path . 'school_info.php'],
                    ['icon' => 'person-lines-fill', 'title' => 'Users', 'url' => $base_path . 'users.php'],
                    ['icon' => 'gear', 'title' => 'Settings', 'url' => $base_path . 'settings.php']
                ]
            ];
            break;
            
        case 'teacher':
            $menu_categories = [
                'Dashboard' => [
                    ['icon' => 'speedometer2', 'title' => 'Dashboard', 'url' => '../teacher/dashboard.php']
                ],
                'Class Management' => [
                    ['icon' => 'building', 'title' => 'My Classes', 'url' => '../teacher/classes.php'],
                    ['icon' => 'people', 'title' => 'Students', 'url' => '../teacher/students.php'],
                    ['icon' => 'calendar-check', 'title' => 'Attendance', 'url' => '../teacher/attendance.php']
                ],
                'Academic Records' => [
                    ['icon' => 'journal-text', 'title' => 'Academic Records', 'url' => '../teacher/academics.php'],
                    ['icon' => 'graph-up', 'title' => 'Reports', 'url' => '../teacher/reports.php']
                ],
                'Profile' => [
                    ['icon' => 'person-circle', 'title' => 'My Profile', 'url' => '../teacher/profile.php']
                ]
            ];
            break;
            
        case 'cashier':
            $menu_categories = [
                'Dashboard' => [
                    ['icon' => 'speedometer2', 'title' => 'Dashboard', 'url' => '../cashier/dashboard.php']
                ],
                'Fee Management' => [
                    ['icon' => 'currency-rupee', 'title' => 'Fee Collection', 'url' => '../cashier/fees.php'],
                    ['icon' => 'receipt', 'title' => 'Receipts', 'url' => '../cashier/receipt.php'],
                    ['icon' => 'graph-up', 'title' => 'Fee Reports', 'url' => '../cashier/reports.php']
                ],
                'Student Search' => [
                    ['icon' => 'search', 'title' => 'Find Students', 'url' => '../cashier/students.php']
                ],
                'Profile' => [
                    ['icon' => 'person-circle', 'title' => 'My Profile', 'url' => '../cashier/profile.php']
                ]
            ];
            break;
            
        case 'student':
            $menu_categories = [
                'Dashboard' => [
                    ['icon' => 'speedometer2', 'title' => 'Dashboard', 'url' => '../student/dashboard.php']
                ],
                'Academic' => [
                    ['icon' => 'journal-text', 'title' => 'Academics', 'url' => '../student/academics.php'],
                    ['icon' => 'calendar-check', 'title' => 'Attendance', 'url' => '../student/attendance.php'],
                    ['icon' => 'file-earmark-text', 'title' => 'Documents', 'url' => '../student/documents.php']
                ],
                'Financial' => [
                    ['icon' => 'currency-rupee', 'title' => 'Fee History', 'url' => '../student/fees.php']
                ],
                'Profile' => [
                    ['icon' => 'person-circle', 'title' => 'My Profile', 'url' => '../student/profile.php']
                ]
            ];
            break;
    }
    
    return $menu_categories;
}

$menu_categories = getMenuCategories($user_role, $base_path);
?>

<aside class="sidebar" id="sidebar">
    <!-- Logo Section -->
    <div class="sidebar-header">
        <div class="sidebar-toggle">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </div>
        <div class="sidebar-brand">
            <div class="brand-wrapper">
                <div class="brand-logo">
                    <?php echo getSchoolLogo('lg'); ?>
                </div>
                <div class="brand-text">
                    <h2 class="brand-title"><?php echo getSchoolName(); ?></h2>
                    <p class="brand-subtitle">Higher Secondary School</p>
                    <span class="user-badge"><?php echo ucfirst($user_role); ?> Panel</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <div class="nav-menu">
            <?php foreach ($menu_categories as $category => $items): ?>
                <?php if (!empty($items)): ?>
                    <div class="menu-section">
                        <?php if (count($items) == 1): ?>
                            <!-- Single item - no submenu -->
                            <?php $item = $items[0]; ?>
                            <a href="<?php echo $item['url']; ?>" 
                               class="menu-link <?php echo (basename($_SERVER['PHP_SELF']) == basename($item['url'])) ? 'active' : ''; ?>">
                                <i class="bi bi-<?php echo $item['icon']; ?>"></i>
                                <span class="menu-text"><?php echo $item['title']; ?></span>
                            </a>
                        <?php else: ?>
                            <!-- Multiple items - create submenu -->
                            <button class="menu-toggle" 
                                    aria-expanded="false" aria-controls="submenu-<?php echo sanitize_string($category); ?>">
                                <i class="bi bi-<?php echo getFirstIcon($items); ?>"></i>
                                <span class="menu-text"><?php echo $category; ?></span>
                                <i class="bi bi-chevron-down submenu-arrow"></i>
                            </button>
                            <ul class="submenu" id="submenu-<?php echo sanitize_string($category); ?>">
                                <?php foreach ($items as $item): ?>
                                    <li class="submenu-item">
                                        <a href="<?php echo $item['url']; ?>" 
                                           class="submenu-link <?php echo (basename($_SERVER['PHP_SELF']) == basename($item['url'])) ? 'active' : ''; ?>">
                                            <i class="bi bi-<?php echo $item['icon']; ?>"></i>
                                            <span class="submenu-text"><?php echo $item['title']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="user-role-text"><?php echo ucfirst($user_role); ?></div>
            </div>
        </div>
    </div>
</aside>

<?php
// Helper functions
function sanitize_string($string) {
    return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $string));
}

function getFirstIcon($items) {
    return !empty($items) ? $items[0]['icon'] : 'circle';
}
?>
