<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Redirect to enhanced dashboard for better user experience
header('Location: enhanced_dashboard.php');
exit();

// Get dashboard statistics
$stats = [];

// Total students
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students");
$stmt->execute();
$stats['total_students'] = $stmt->fetch()['count'];

// Total teachers
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$stmt->execute();
$stats['total_teachers'] = $stmt->fetch()['count'];

// Total fee collected this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM fee_payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
$stmt->execute();
$stats['monthly_fee_collection'] = $stmt->fetch()['total'];

// Total expenses this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())");
$stmt->execute();
$stats['monthly_expenses'] = $stmt->fetch()['total'];

// Recent fee payments
$stmt = $pdo->prepare("
    SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name 
    FROM fee_payments fp 
    JOIN students s ON fp.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY fp.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_payments = $stmt->fetchAll();

// Recent student admissions
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY s.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_admissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - A.S.ACADEMY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="../assets/js/sidebar.js" defer></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <div class="page-header-main">
                        <div class="page-header-text">
                            <h1 class="page-title">Admin Dashboard</h1>
                            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                        </div>
                        <!-- Academic Year Selector -->
                        <div class="academic-year-container">
                            <?php renderAcademicYearSelector(); ?>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Statistics -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Total Students</div>
                            <div class="stat-icon" style="background: var(--primary-color);">👥</div>
                        </div>
                        <div class="stat-value" data-stat="total_students"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-change positive">Active enrollments</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Total Teachers</div>
                            <div class="stat-icon" style="background: var(--success-color);">👨‍🏫</div>
                        </div>
                        <div class="stat-value" data-stat="total_teachers"><?php echo $stats['total_teachers']; ?></div>
                        <div class="stat-change">Faculty members</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Monthly Fee Collection</div>
                            <div class="stat-icon" style="background: var(--warning-color);">💰</div>
                        </div>
                        <div class="stat-value" data-stat="monthly_fee_collection">₹<?php echo number_format($stats['monthly_fee_collection'], 2); ?></div>
                        <div class="stat-change">This month</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Monthly Expenses</div>
                            <div class="stat-icon" style="background: var(--danger-color);">💳</div>
                        </div>
                        <div class="stat-value" data-stat="monthly_expenses">₹<?php echo number_format($stats['monthly_expenses'], 2); ?></div>
                        <div class="stat-change">This month</div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <!-- Recent Fee Payments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Fee Payments</h3>
                            <a href="fees.php" class="btn btn-outline">View All</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_payments)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No recent payments</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                                    <small class="d-block text-secondary"><?php echo htmlspecialchars($payment['admission_no']); ?></small>
                                                </td>
                                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Admissions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Admissions</h3>
                            <a href="students.php" class="btn btn-outline">View All</a>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_admissions)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No recent admissions</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_admissions as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_name'] ?? 'Not Assigned'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($student['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="students.php?action=add" class="btn btn-primary">
                            👥 Add New Student
                        </a>
                        <a href="teachers.php?action=add" class="btn btn-success">
                            👨‍🏫 Add New Teacher
                        </a>
                        <a href="fees.php?action=collect" class="btn btn-warning">
                            💰 Collect Fee
                        </a>
                        <a href="expenses.php?action=add" class="btn btn-danger">
                            💳 Add Expense
                        </a>
                        <a href="reports.php" class="btn btn-secondary">
                            📈 Generate Reports
                        </a>
                        <a href="school_info.php" class="btn btn-outline">
                            🏢 School Settings
                        </a>
                    </div>
                </div>

                <!-- Monthly Summary Chart (placeholder) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Monthly Financial Summary</h3>
                        <button class="btn btn-outline" onclick="refreshDashboardStats()">
                            🔄 Refresh
                        </button>
                    </div>
                    <div style="padding: 2rem; text-align: center;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <h4 style="color: var(--success-color); margin-bottom: 1rem;">Fee Collection</h4>
                                <div style="font-size: 2rem; font-weight: 700; color: var(--success-color);">
                                    ₹<?php echo number_format($stats['monthly_fee_collection'], 2); ?>
                                </div>
                            </div>
                            <div>
                                <h4 style="color: var(--danger-color); margin-bottom: 1rem;">Expenses</h4>
                                <div style="font-size: 2rem; font-weight: 700; color: var(--danger-color);">
                                    ₹<?php echo number_format($stats['monthly_expenses'], 2); ?>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                            <h5>Net Balance: 
                                <span style="color: <?php echo ($stats['monthly_fee_collection'] - $stats['monthly_expenses']) >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                                    ₹<?php echo number_format($stats['monthly_fee_collection'] - $stats['monthly_expenses'], 2); ?>
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Auto-refresh dashboard stats every 5 minutes
        setInterval(refreshDashboardStats, 300000);
        
        // Initialize dashboard-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add print buttons to dashboard tables
            setupPrintFunctionality();
        });
    </script>
    
    <style>
    /* Dashboard Page Header Styling */
    .page-header-main {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 2rem;
        flex-wrap: wrap;
    }
    
    .page-header-text {
        flex: 1;
        min-width: 300px;
    }
    
    .academic-year-container {
        flex-shrink: 0;
        margin-top: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .page-header-main {
            flex-direction: column;
            gap: 1rem;
        }
        
        .academic-year-container {
            align-self: flex-start;
            margin-top: 0;
        }
    }
    </style>
</body>
</html>
