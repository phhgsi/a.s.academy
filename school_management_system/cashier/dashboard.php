<?php
require_once '../config/database.php';

// Check if user is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

// Get dashboard statistics for cashier
$stats = [];

// Today's fee collection
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM fee_payments 
    WHERE DATE(payment_date) = CURRENT_DATE() AND collected_by = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats['today_collection'] = $stmt->fetch()['total'];

// This month's fee collection by cashier
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM fee_payments 
    WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
    AND YEAR(payment_date) = YEAR(CURRENT_DATE()) 
    AND collected_by = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats['month_collection'] = $stmt->fetch()['total'];

// Total payments collected by cashier
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM fee_payments WHERE collected_by = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats['total_receipts'] = $stmt->fetch()['count'];

// Recent fee payments by this cashier
$stmt = $pdo->prepare("
    SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name 
    FROM fee_payments fp 
    JOIN students s ON fp.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE fp.collected_by = ?
    ORDER BY fp.created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recent_payments = $stmt->fetchAll();

// Today's total collection (all cashiers)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM fee_payments 
    WHERE DATE(payment_date) = CURRENT_DATE()
");
$stmt->execute();
$stats['school_today_collection'] = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - A.S.ACADEMY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">Cashier Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>

                <!-- Dashboard Statistics -->
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Today's Collection (Me)</div>
                            <div class="stat-icon" style="background: var(--success-color);">💰</div>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($stats['today_collection'], 2); ?></div>
                        <div class="stat-change positive">My collections</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">This Month (Me)</div>
                            <div class="stat-icon" style="background: var(--primary-color);">📊</div>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($stats['month_collection'], 2); ?></div>
                        <div class="stat-change">Monthly total</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Total Receipts</div>
                            <div class="stat-icon" style="background: var(--warning-color);">🧾</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_receipts']; ?></div>
                        <div class="stat-change">All time</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">School Total Today</div>
                            <div class="stat-icon" style="background: var(--secondary-color);">🏫</div>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($stats['school_today_collection'], 2); ?></div>
                        <div class="stat-change">All cashiers</div>
                    </div>
                </div>

                <!-- Recent Fee Collections -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">My Recent Collections</h3>
                        <a href="fees.php" class="btn btn-outline">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Student Details</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_payments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No fee payments collected yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br>
                                                <small class="text-secondary">
                                                    <?php echo htmlspecialchars($payment['admission_no']); ?> | 
                                                    <?php echo htmlspecialchars($payment['class_name']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $payment['payment_method'] == 'cash' ? 'warning' : 'success'; ?>">
                                                    <?php echo ucfirst($payment['payment_method']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;" target="_blank">🧾 Receipt</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="fees.php?action=collect" class="btn btn-primary">
                            💰 Collect Fee
                        </a>
                        <a href="receipts.php" class="btn btn-success">
                            🧾 View Receipts
                        </a>
                        <a href="students.php" class="btn btn-secondary">
                            👥 Search Students
                        </a>
                        <a href="reports.php" class="btn btn-outline">
                            📈 Fee Reports
                        </a>
                    </div>
                </div>

                <!-- Collection Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Collection Summary</h3>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; text-align: center; padding: 2rem;">
                        <div>
                            <h4 style="color: var(--success-color); margin-bottom: 1rem;">My Performance</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-color);">
                                        ₹<?php echo number_format($stats['today_collection'], 2); ?>
                                    </div>
                                    <small>Today's Collection</small>
                                </div>
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                        ₹<?php echo number_format($stats['month_collection'], 2); ?>
                                    </div>
                                    <small>This Month</small>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--primary-color); margin-bottom: 1rem;">School Total</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary-color);">
                                        ₹<?php echo number_format($stats['school_today_collection'], 2); ?>
                                    </div>
                                    <small>Today's School Collection</small>
                                </div>
                                <div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning-color);">
                                        <?php echo $stats['total_receipts']; ?>
                                    </div>
                                    <small>My Total Receipts</small>
                                </div>
                            </div>
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
        // Auto-refresh dashboard stats every 2 minutes for cashier
        setInterval(refreshDashboardStats, 120000);
    </script>
</body>
</html>
