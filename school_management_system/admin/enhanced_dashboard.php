<?php
require_once '../config/database.php';
require_once '../includes/academic_year.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Check if user is admin
requireAuth('admin');

// Get current academic year
$current_academic_year = $_SESSION['current_academic_year'] ?? '2024-2025';

// Enhanced Dashboard Statistics
$stats = [];

// Total students with trend - remove is_active check if column doesn't exist
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as current_count,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
    FROM students 
    WHERE academic_year = ?
");
$stmt->execute([$current_academic_year]);
$student_stats = $stmt->fetch();
$stats['students'] = $student_stats;

// Total teachers with active status - remove is_active check if column doesn't exist
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as current_count,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
    FROM users 
    WHERE role = 'teacher'
");
$stmt->execute();
$teacher_stats = $stmt->fetch();
$stats['teachers'] = $teacher_stats;

// Fee collection statistics
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount_paid), 0) as total_this_month,
        COALESCE(SUM(CASE WHEN payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN amount_paid END), 0) as total_this_week,
        COUNT(*) as transactions_this_month,
        COALESCE(SUM(CASE WHEN MONTH(payment_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN amount_paid END), 0) as total_last_month
    FROM fee_payments 
    WHERE MONTH(payment_date) = MONTH(CURDATE()) 
    AND YEAR(payment_date) = YEAR(CURDATE())
    AND academic_year = ?
");
$stmt->execute([$current_academic_year]);
$fee_stats = $stmt->fetch();
$stats['fees'] = $fee_stats;

// Expense statistics
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_this_month,
        COUNT(*) as transactions_this_month,
        COALESCE(SUM(CASE WHEN MONTH(expense_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN amount END), 0) as total_last_month
    FROM expenses 
    WHERE MONTH(expense_date) = MONTH(CURDATE()) 
    AND YEAR(expense_date) = YEAR(CURDATE())
");
$stmt->execute();
$expense_stats = $stmt->fetch();
$stats['expenses'] = $expense_stats;

// Attendance statistics for today
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_marked,
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
        ROUND((COUNT(CASE WHEN status = 'present' THEN 1 END) / COUNT(*)) * 100, 2) as attendance_percentage
    FROM attendance 
    WHERE attendance_date = CURDATE()
");
$stmt->execute();
$attendance_stats = $stmt->fetch();
$stats['attendance'] = $attendance_stats;

// Pending fee collection - remove is_active check if column doesn't exist
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT sfs.student_id) as students_with_dues,
        COALESCE(SUM(sfs.total_amount - sfs.paid_amount), 0) as total_pending
    FROM student_fee_status sfs
    JOIN students s ON sfs.student_id = s.id
    WHERE sfs.status IN ('pending', 'partial') 
    AND sfs.academic_year = ?
");
$stmt->execute([$current_academic_year]);
$pending_fees = $stmt->fetch();
$stats['pending_fees'] = $pending_fees;

// Recent activities (last 10)
$stmt = $pdo->prepare("
    SELECT al.*, u.full_name as user_name
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Upcoming events (next 7 days)
$stmt = $pdo->prepare("
    SELECT * FROM events 
    WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY event_date ASC, start_time ASC
    LIMIT 5
");
$stmt->execute();
$upcoming_events = $stmt->fetchAll();

// Class-wise student distribution - remove is_active checks if columns don't exist
$stmt = $pdo->prepare("
    SELECT c.class_name, c.section, COUNT(s.id) as student_count
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    WHERE c.academic_year = ?
    GROUP BY c.id
    ORDER BY c.class_name, c.section
");
$stmt->execute([$current_academic_year]);
$class_distribution = $stmt->fetchAll();

// Monthly fee collection trend (last 6 months)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        MONTHNAME(payment_date) as month_name,
        COALESCE(SUM(amount_paid), 0) as total_amount,
        COUNT(*) as transaction_count
    FROM fee_payments 
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute();
$fee_trend = $stmt->fetchAll();

// Calculate trends
function calculateTrend($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$fee_trend_percentage = calculateTrend($fee_stats['total_this_month'], $fee_stats['total_last_month']);
$expense_trend_percentage = calculateTrend($expense_stats['total_this_month'], $expense_stats['total_last_month']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Admin Dashboard - A.S.ACADEMY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-enhanced {
            animation: fadeIn 0.6s ease-out;
        }
        
        .quick-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card-enhanced:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-enhanced:hover::before {
            transform: scaleX(1);
        }
        
        .stat-header-enhanced {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-icon-enhanced {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-value-enhanced {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .trend-positive {
            color: #10b981;
        }
        
        .trend-negative {
            color: #ef4444;
        }
        
        .trend-neutral {
            color: #6b7280;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: white;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .activity-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .events-widget {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .event-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .event-date {
            text-align: center;
            min-width: 60px;
        }
        
        .event-day {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3b82f6;
            line-height: 1;
        }
        
        .event-month {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
        }
        
        .event-details {
            flex: 1;
        }
        
        .event-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .event-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .quick-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .stat-card-enhanced {
                padding: 1.25rem;
            }
            
            .stat-value-enhanced {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper dashboard-enhanced">
                <div class="page-header">
                    <div class="page-header-main">
                        <div class="page-header-text">
                            <h1 class="page-title">Enhanced Admin Dashboard</h1>
                            <p class="page-subtitle">Comprehensive overview and analytics • <?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <!-- Academic Year Selector -->
                        <div class="academic-year-container">
                            <?php renderAcademicYearSelector(); ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Statistics Grid -->
                <div class="quick-stats-grid">
                    <!-- Students Card -->
                    <div class="stat-card-enhanced">
                        <div class="stat-header-enhanced">
                            <div class="stat-info">
                                <div class="stat-label">Total Students</div>
                                <div class="stat-value-enhanced"><?php echo number_format($stats['students']['current_count']); ?></div>
                                <div class="stat-trend trend-positive">
                                    <i class="bi bi-arrow-up"></i>
                                    <?php echo $stats['students']['new_this_month']; ?> new this month
                                </div>
                            </div>
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Teachers Card -->
                    <div class="stat-card-enhanced">
                        <div class="stat-header-enhanced">
                            <div class="stat-info">
                                <div class="stat-label">Active Teachers</div>
                                <div class="stat-value-enhanced"><?php echo number_format($stats['teachers']['current_count']); ?></div>
                                <div class="stat-trend trend-neutral">
                                    <i class="bi bi-mortarboard-fill"></i>
                                    Faculty members
                                </div>
                            </div>
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="bi bi-person-workspace"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Collection Card -->
                    <div class="stat-card-enhanced">
                        <div class="stat-header-enhanced">
                            <div class="stat-info">
                                <div class="stat-label">Monthly Collection</div>
                                <div class="stat-value-enhanced">₹<?php echo number_format($stats['fees']['total_this_month'], 0); ?></div>
                                <div class="stat-trend <?php echo $fee_trend_percentage >= 0 ? 'trend-positive' : 'trend-negative'; ?>">
                                    <i class="bi bi-arrow-<?php echo $fee_trend_percentage >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($fee_trend_percentage); ?>% vs last month
                                </div>
                            </div>
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="bi bi-wallet-fill"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Expenses Card -->
                    <div class="stat-card-enhanced">
                        <div class="stat-header-enhanced">
                            <div class="stat-info">
                                <div class="stat-label">Monthly Expenses</div>
                                <div class="stat-value-enhanced">₹<?php echo number_format($stats['expenses']['total_this_month'], 0); ?></div>
                                <div class="stat-trend <?php echo $expense_trend_percentage <= 0 ? 'trend-positive' : 'trend-negative'; ?>">
                                    <i class="bi bi-arrow-<?php echo $expense_trend_percentage <= 0 ? 'down' : 'up'; ?>"></i>
                                    <?php echo abs($expense_trend_percentage); ?>% vs last month
                                </div>
                            </div>
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                <i class="bi bi-credit-card-fill"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Card -->
                    <div class="stat-card-enhanced">
                        <div class="stat-header-enhanced">
                            <div class="stat-info">
                                <div class="stat-label">Today's Attendance</div>
                                <div class="stat-value-enhanced"><?php echo $stats['attendance']['attendance_percentage'] ?? 0; ?>%</div>
                                <div class="stat-trend trend-neutral">
                                    <i class="bi bi-calendar-check"></i>
                                    <?php echo $stats['attendance']['present_count'] ?? 0; ?>/<?php echo $stats['attendance']['total_marked'] ?? 0; ?> present
                                </div>
                            </div>
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                <i class="bi bi-clipboard-check-fill"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Fees Card -->
                    <div class="stat-card-enhanced">
                        <div class="stat-header-enhanced">
                            <div class="stat-info">
                                <div class="stat-label">Pending Fees</div>
                                <div class="stat-value-enhanced">₹<?php echo number_format($stats['pending_fees']['total_pending'], 0); ?></div>
                                <div class="stat-trend trend-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <?php echo $stats['pending_fees']['students_with_dues']; ?> students
                                </div>
                            </div>
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics and Activities Section -->
                <div class="analytics-grid">
                    <!-- Fee Collection Trend Chart -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <i class="bi bi-graph-up"></i>
                            Fee Collection Trend (Last 6 Months)
                        </h3>
                        <canvas id="feeCollectionChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Recent Activities Feed -->
                    <div class="activity-feed">
                        <h3 class="chart-title">
                            <i class="bi bi-activity"></i>
                            Recent Activities
                        </h3>
                        
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                No recent activities
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon" style="background: <?php 
                                        echo match($activity['action']) {
                                            'create' => '#10b981',
                                            'update' => '#f59e0b', 
                                            'delete' => '#ef4444',
                                            'login' => '#3b82f6',
                                            'payment' => '#8b5cf6',
                                            default => '#6b7280'
                                        };
                                    ?>;">
                                        <i class="bi bi-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo ucfirst($activity['action']); ?> Action</div>
                                        <div class="activity-meta">
                                            <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?> • 
                                            <?php echo timeAgo($activity['created_at']); ?>
                                            <?php if ($activity['table_name']): ?>
                                                • <?php echo ucfirst($activity['table_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Events and Class Distribution -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <!-- Upcoming Events -->
                    <div class="events-widget">
                        <h3 class="chart-title">
                            <i class="bi bi-calendar-event"></i>
                            Upcoming Events
                        </h3>
                        
                        <?php if (empty($upcoming_events)): ?>
                            <div class="text-center text-muted">
                                <i class="bi bi-calendar-x" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                No upcoming events
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcoming_events as $event): ?>
                                <div class="event-item">
                                    <div class="event-date">
                                        <div class="event-day"><?php echo date('j', strtotime($event['event_date'])); ?></div>
                                        <div class="event-month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                    </div>
                                    <div class="event-details">
                                        <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                        <div class="event-meta">
                                            <?php if ($event['start_time']): ?>
                                                <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                            <?php endif; ?>
                                            <?php if ($event['location']): ?>
                                                • <?php echo htmlspecialchars($event['location']); ?>
                                            <?php endif; ?>
                                            • <?php echo ucfirst($event['event_type']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Class Distribution Chart -->
                    <div class="chart-container">
                        <h3 class="chart-title">
                            <i class="bi bi-pie-chart-fill"></i>
                            Class Distribution
                        </h3>
                        <canvas id="classDistributionChart" width="400" height="300"></canvas>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div class="quick-actions">
                    <a href="students.php" class="quick-action-card">
                        <div class="quick-action-icon" style="color: #3b82f6;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h4>Add Student</h4>
                        <p>Register new student</p>
                    </a>
                    
                    <a href="attendance.php" class="quick-action-card">
                        <div class="quick-action-icon" style="color: #10b981;">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h4>Mark Attendance</h4>
                        <p>Record daily attendance</p>
                    </a>
                    
                    <a href="fees.php" class="quick-action-card">
                        <div class="quick-action-icon" style="color: #f59e0b;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h4>Collect Fees</h4>
                        <p>Process fee payments</p>
                    </a>
                    
                    <a href="reports.php" class="quick-action-card">
                        <div class="quick-action-icon" style="color: #8b5cf6;">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h4>View Reports</h4>
                        <p>Generate analytics</p>
                    </a>
                    
                    <a href="messages.php" class="quick-action-card">
                        <div class="quick-action-icon" style="color: #ef4444;">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <h4>Send Message</h4>
                        <p>Communicate with users</p>
                    </a>
                    
                    <a href="settings.php" class="quick-action-card">
                        <div class="quick-action-icon" style="color: #6b7280;">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <h4>System Settings</h4>
                        <p>Configure system</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Include overlay for mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Scripts -->
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/modern-ui.js"></script>
    
    <script>
        // Fee Collection Trend Chart
        const feeCtx = document.getElementById('feeCollectionChart').getContext('2d');
        const feeData = <?php echo json_encode($fee_trend); ?>;
        
        new Chart(feeCtx, {
            type: 'line',
            data: {
                labels: feeData.map(item => item.month_name || item.month),
                datasets: [{
                    label: 'Fee Collection',
                    data: feeData.map(item => parseFloat(item.total_amount)),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                },
                elements: {
                    line: {
                        borderWidth: 3
                    }
                }
            }
        });

        // Class Distribution Chart
        const classCtx = document.getElementById('classDistributionChart').getContext('2d');
        const classData = <?php echo json_encode($class_distribution); ?>;
        
        new Chart(classCtx, {
            type: 'doughnut',
            data: {
                labels: classData.map(item => `${item.class_name} ${item.section}`),
                datasets: [{
                    data: classData.map(item => parseInt(item.student_count)),
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                        '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Real-time updates every 30 seconds
        setInterval(function() {
            updateDashboardStats();
        }, 30000);

        function updateDashboardStats() {
            fetch('ajax_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stat values with animation
                        Object.keys(data.stats).forEach(key => {
                            const element = document.querySelector(`[data-stat="${key}"]`);
                            if (element && element.textContent !== data.stats[key]) {
                                element.style.transform = 'scale(1.1)';
                                element.textContent = data.stats[key];
                                setTimeout(() => {
                                    element.style.transform = 'scale(1)';
                                }, 200);
                            }
                        });
                    }
                })
                .catch(error => console.log('Dashboard update error:', error));
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to quick action cards
            document.querySelectorAll('.quick-action-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    this.style.opacity = '0.7';
                    this.style.transform = 'scale(0.98)';
                });
            });

            // Auto-refresh notifications
            if (typeof updateNotifications === 'function') {
                setInterval(updateNotifications, 60000); // Every minute
            }
        });
    </script>
</body>
</html>
