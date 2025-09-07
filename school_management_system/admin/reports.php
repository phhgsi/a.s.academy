<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$report_type = $_GET['type'] ?? 'selection';
$message = '';

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $class_filter = $_POST['class_filter'] ?? '';
    $village_filter = $_POST['village_filter'] ?? '';
}

// Get classes for filters
$stmt = $pdo->prepare("SELECT * FROM classes WHERE is_active = 1 ORDER BY class_name");
$stmt->execute();
$classes = $stmt->fetchAll();

// Get villages for filters
$stmt = $pdo->prepare("SELECT DISTINCT village FROM students WHERE is_active = 1 AND village IS NOT NULL ORDER BY village");
$stmt->execute();
$villages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .header { display: none !important; }
            .main-content { margin-left: 0 !important; padding-top: 0 !important; }
            body { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header no-print">
                    <h1 class="page-title">Reports & Analytics</h1>
                    <p class="page-subtitle">Generate comprehensive reports for school management</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?> no-print">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($report_type == 'selection'): ?>
                    <!-- Report Selection -->
                    <div class="form-container no-print">
                        <form method="POST">
                            <h3 style="margin-bottom: 2rem; color: var(--primary-color);">📈 Generate Report</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Report Type *</label>
                                    <select name="report_type" class="form-select" required>
                                        <option value="">Select Report Type</option>
                                        <option value="students">Students Report</option>
                                        <option value="fees">Fee Collection Report</option>
                                        <option value="expenses">Expenses Report</option>
                                        <option value="attendance">Attendance Report</option>
                                        <option value="academic">Academic Performance Report</option>
                                        <option value="financial">Financial Summary Report</option>
                                        <option value="class_wise">Class-wise Summary</option>
                                        <option value="village_wise">Village-wise Report</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">From Date</label>
                                    <input type="date" name="from_date" class="form-input" 
                                           value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">To Date</label>
                                    <input type="date" name="to_date" class="form-input" 
                                           value="<?php echo date('Y-m-t'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Filter by Class</label>
                                    <select name="class_filter" class="form-select">
                                        <option value="">All Classes</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>">
                                                <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Filter by Village</label>
                                    <select name="village_filter" class="form-select">
                                        <option value="">All Villages</option>
                                        <?php foreach ($villages as $village): ?>
                                            <option value="<?php echo $village['village']; ?>">
                                                <?php echo htmlspecialchars($village['village']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Academic Year</label>
                                    <select name="academic_year" class="form-select">
                                        <option value="">All Years</option>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($i = $current_year - 3; $i <= $current_year + 1; $i++): 
                                            $year_text = $i . '-' . ($i + 1);
                                        ?>
                                            <option value="<?php echo $year_text; ?>" <?php echo ($i == $current_year) ? 'selected' : ''; ?>>
                                                <?php echo $year_text; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" name="generate_report" class="btn btn-primary">📈 Generate Report</button>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Report Links -->
                    <div class="card no-print">
                        <div class="card-header">
                            <h3 class="card-title">Quick Reports</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <a href="?type=students&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-t'); ?>" class="btn btn-outline">
                                👥 All Students Report
                            </a>
                            <a href="?type=fees&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-t'); ?>" class="btn btn-outline">
                                💰 Monthly Fee Collection
                            </a>
                            <a href="?type=expenses&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-t'); ?>" class="btn btn-outline">
                                💳 Monthly Expenses
                            </a>
                            <a href="?type=financial&from_date=<?php echo date('Y-m-01'); ?>&to_date=<?php echo date('Y-m-t'); ?>" class="btn btn-outline">
                                📊 Financial Summary
                            </a>
                            <a href="?type=class_wise" class="btn btn-outline">
                                🏫 Class-wise Report
                            </a>
                            <a href="?type=village_wise" class="btn btn-outline">
                                🌍 Village-wise Report
                            </a>
                        </div>
                    </div>

                <?php elseif ($report_type == 'students'): ?>
                    <!-- Students Report -->
                    <?php
                    $where_conditions = ['s.is_active = 1'];
                    $params = [];
                    
                    if (!empty($_GET['class_filter'])) {
                        $where_conditions[] = 's.class_id = ?';
                        $params[] = $_GET['class_filter'];
                    }
                    
                    if (!empty($_GET['village_filter'])) {
                        $where_conditions[] = 's.village = ?';
                        $params[] = $_GET['village_filter'];
                    }
                    
                    $where_clause = implode(' AND ', $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT s.*, c.class_name, c.section 
                        FROM students s 
                        LEFT JOIN classes c ON s.class_id = c.id 
                        WHERE $where_clause
                        ORDER BY c.class_name, s.admission_no
                    ");
                    $stmt->execute($params);
                    $students = $stmt->fetchAll();
                    ?>
                    
                    <div class="card">
                        <div class="card-header no-print">
                            <h3 class="card-title">Students Report</h3>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
                                <button class="btn btn-outline" onclick="exportToCSV('studentsReportTable', 'students_report')">📊 Export CSV</button>
                                <a href="?" class="btn btn-secondary">← Back</a>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: var(--light-color); margin-bottom: 1rem;">
                            <h4>Students Report</h4>
                            <p>Generated on: <?php echo date('d/m/Y H:i'); ?></p>
                            <p>Total Students: <?php echo count($students); ?></p>
                        </div>
                        
                        <div class="table-container">
                            <table class="table" id="studentsReportTable">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Father's Name</th>
                                        <th>Class</th>
                                        <th>Village</th>
                                        <th>Mobile</th>
                                        <th>DOB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No students found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $index => $student): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                                                <td><?php echo htmlspecialchars($student['village']); ?></td>
                                                <td><?php echo htmlspecialchars($student['parent_mobile']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($student['date_of_birth'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($report_type == 'fees'): ?>
                    <!-- Fee Collection Report -->
                    <?php
                    $where_conditions = ['1=1'];
                    $params = [];
                    
                    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
                        $where_conditions[] = 'fp.payment_date BETWEEN ? AND ?';
                        $params[] = $_GET['from_date'];
                        $params[] = $_GET['to_date'];
                    }
                    
                    if (!empty($_GET['class_filter'])) {
                        $where_conditions[] = 's.class_id = ?';
                        $params[] = $_GET['class_filter'];
                    }
                    
                    $where_clause = implode(' AND ', $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name, u.full_name as collected_by_name
                        FROM fee_payments fp 
                        JOIN students s ON fp.student_id = s.id 
                        LEFT JOIN classes c ON s.class_id = c.id 
                        LEFT JOIN users u ON fp.collected_by = u.id
                        WHERE $where_clause
                        ORDER BY fp.payment_date DESC
                    ");
                    $stmt->execute($params);
                    $fee_payments = $stmt->fetchAll();
                    
                    $total_collection = array_sum(array_column($fee_payments, 'amount'));
                    ?>
                    
                    <div class="card">
                        <div class="card-header no-print">
                            <h3 class="card-title">Fee Collection Report</h3>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
                                <button class="btn btn-outline" onclick="exportToCSV('feeReportTable', 'fee_collection_report')">📊 Export CSV</button>
                                <a href="?" class="btn btn-secondary">← Back</a>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: var(--light-color); margin-bottom: 1rem;">
                            <h4>Fee Collection Report</h4>
                            <p>Period: <?php echo date('d/m/Y', strtotime($_GET['from_date'] ?? date('Y-m-01'))); ?> to <?php echo date('d/m/Y', strtotime($_GET['to_date'] ?? date('Y-m-t'))); ?></p>
                            <p>Total Collection: <strong>₹<?php echo number_format($total_collection, 2); ?></strong></p>
                            <p>Total Receipts: <?php echo count($fee_payments); ?></p>
                        </div>
                        
                        <div class="table-container">
                            <table class="table" id="feeReportTable">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Receipt No</th>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Collected By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fee_payments)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No fee payments found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fee_payments as $index => $payment): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['collected_by_name']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: var(--light-color); font-weight: 600;">
                                        <td colspan="6" class="text-right"><strong>Total Collection:</strong></td>
                                        <td><strong>₹<?php echo number_format($total_collection, 2); ?></strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                <?php elseif ($report_type == 'expenses'): ?>
                    <!-- Expenses Report -->
                    <?php
                    $where_conditions = ['1=1'];
                    $params = [];
                    
                    if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
                        $where_conditions[] = 'e.expense_date BETWEEN ? AND ?';
                        $params[] = $_GET['from_date'];
                        $params[] = $_GET['to_date'];
                    }
                    
                    $where_clause = implode(' AND ', $where_conditions);
                    
                    $stmt = $pdo->prepare("
                        SELECT e.*, u1.full_name as created_by_name, u2.full_name as approved_by_name
                        FROM expenses e 
                        LEFT JOIN users u1 ON e.created_by = u1.id 
                        LEFT JOIN users u2 ON e.approved_by = u2.id
                        WHERE $where_clause
                        ORDER BY e.expense_date DESC
                    ");
                    $stmt->execute($params);
                    $expenses = $stmt->fetchAll();
                    
                    $total_expenses = array_sum(array_column($expenses, 'amount'));
                    ?>
                    
                    <div class="card">
                        <div class="card-header no-print">
                            <h3 class="card-title">Expenses Report</h3>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
                                <button class="btn btn-outline" onclick="exportToCSV('expensesReportTable', 'expenses_report')">📊 Export CSV</button>
                                <a href="?" class="btn btn-secondary">← Back</a>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: var(--light-color); margin-bottom: 1rem;">
                            <h4>Expenses Report</h4>
                            <p>Period: <?php echo date('d/m/Y', strtotime($_GET['from_date'] ?? date('Y-m-01'))); ?> to <?php echo date('d/m/Y', strtotime($_GET['to_date'] ?? date('Y-m-t'))); ?></p>
                            <p>Total Expenses: <strong>₹<?php echo number_format($total_expenses, 2); ?></strong></p>
                            <p>Total Records: <?php echo count($expenses); ?></p>
                        </div>
                        
                        <div class="table-container">
                            <table class="table" id="expensesReportTable">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Voucher No</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Reason</th>
                                        <th>Amount</th>
                                        <th>Created By</th>
                                        <th>Approved By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($expenses)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No expenses found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($expenses as $index => $expense): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($expense['voucher_no']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($expense['reason'], 0, 50)) . (strlen($expense['reason']) > 50 ? '...' : ''); ?></td>
                                                <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($expense['created_by_name']); ?></td>
                                                <td><?php echo htmlspecialchars($expense['approved_by_name']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: var(--light-color); font-weight: 600;">
                                        <td colspan="5" class="text-right"><strong>Total Expenses:</strong></td>
                                        <td><strong>₹<?php echo number_format($total_expenses, 2); ?></strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                <?php elseif ($report_type == 'financial'): ?>
                    <!-- Financial Summary Report -->
                    <?php
                    $from_date = $_GET['from_date'] ?? date('Y-m-01');
                    $to_date = $_GET['to_date'] ?? date('Y-m-t');
                    
                    // Get fee collections
                    $stmt = $pdo->prepare("
                        SELECT COALESCE(SUM(amount), 0) as total
                        FROM fee_payments 
                        WHERE payment_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$from_date, $to_date]);
                    $total_income = $stmt->fetch()['total'];
                    
                    // Get expenses
                    $stmt = $pdo->prepare("
                        SELECT COALESCE(SUM(amount), 0) as total
                        FROM expenses 
                        WHERE expense_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$from_date, $to_date]);
                    $total_expenses = $stmt->fetch()['total'];
                    
                    $net_balance = $total_income - $total_expenses;
                    
                    // Get category-wise expenses
                    $stmt = $pdo->prepare("
                        SELECT category, SUM(amount) as total
                        FROM expenses 
                        WHERE expense_date BETWEEN ? AND ?
                        GROUP BY category
                        ORDER BY total DESC
                    ");
                    $stmt->execute([$from_date, $to_date]);
                    $expense_categories = $stmt->fetchAll();
                    
                    // Get fee type-wise collections
                    $stmt = $pdo->prepare("
                        SELECT fee_type, SUM(amount) as total
                        FROM fee_payments 
                        WHERE payment_date BETWEEN ? AND ?
                        GROUP BY fee_type
                        ORDER BY total DESC
                    ");
                    $stmt->execute([$from_date, $to_date]);
                    $fee_types = $stmt->fetchAll();
                    ?>
                    
                    <div class="card">
                        <div class="card-header no-print">
                            <h3 class="card-title">Financial Summary Report</h3>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
                                <a href="?" class="btn btn-secondary">← Back</a>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: var(--light-color); margin-bottom: 1rem;">
                            <h4>Financial Summary Report</h4>
                            <p>Period: <?php echo date('d/m/Y', strtotime($from_date)); ?> to <?php echo date('d/m/Y', strtotime($to_date)); ?></p>
                        </div>
                        
                        <!-- Summary Cards -->
                        <div class="dashboard-grid" style="margin-bottom: 2rem;">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-title">Total Income</div>
                                    <div class="stat-icon" style="background: var(--success-color);">💰</div>
                                </div>
                                <div class="stat-value" style="color: var(--success-color);">₹<?php echo number_format($total_income, 2); ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-title">Total Expenses</div>
                                    <div class="stat-icon" style="background: var(--danger-color);">💳</div>
                                </div>
                                <div class="stat-value" style="color: var(--danger-color);">₹<?php echo number_format($total_expenses, 2); ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-title">Net Balance</div>
                                    <div class="stat-icon" style="background: <?php echo $net_balance >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">📊</div>
                                </div>
                                <div class="stat-value" style="color: <?php echo $net_balance >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                                    ₹<?php echo number_format($net_balance, 2); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detailed Breakdown -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <!-- Fee Collections by Type -->
                            <div>
                                <h5 style="margin-bottom: 1rem; color: var(--primary-color);">Fee Collections by Type</h5>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Fee Type</th>
                                                <th>Amount</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fee_types as $fee_type): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($fee_type['fee_type']); ?></td>
                                                    <td>₹<?php echo number_format($fee_type['total'], 2); ?></td>
                                                    <td><?php echo $total_income > 0 ? number_format(($fee_type['total'] / $total_income) * 100, 1) : 0; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Expenses by Category -->
                            <div>
                                <h5 style="margin-bottom: 1rem; color: var(--primary-color);">Expenses by Category</h5>
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Amount</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expense_categories as $category): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($category['category']); ?></td>
                                                    <td>₹<?php echo number_format($category['total'], 2); ?></td>
                                                    <td><?php echo $total_expenses > 0 ? number_format(($category['total'] / $total_expenses) * 100, 1) : 0; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($report_type == 'class_wise'): ?>
                    <!-- Class-wise Report -->
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT 
                            c.*,
                            COUNT(s.id) as student_count,
                            u.full_name as teacher_name,
                            COALESCE(SUM(fp.amount), 0) as fee_collected
                        FROM classes c 
                        LEFT JOIN students s ON c.id = s.class_id AND s.is_active = 1
                        LEFT JOIN users u ON c.class_teacher_id = u.id
                        LEFT JOIN fee_payments fp ON s.id = fp.student_id AND YEAR(fp.payment_date) = YEAR(CURRENT_DATE())
                        WHERE c.is_active = 1
                        GROUP BY c.id
                        ORDER BY c.class_name, c.section
                    ");
                    $stmt->execute();
                    $class_summary = $stmt->fetchAll();
                    ?>
                    
                    <div class="card">
                        <div class="card-header no-print">
                            <h3 class="card-title">Class-wise Summary Report</h3>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
                                <button class="btn btn-outline" onclick="exportToCSV('classReportTable', 'class_wise_report')">📊 Export CSV</button>
                                <a href="?" class="btn btn-secondary">← Back</a>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: var(--light-color); margin-bottom: 1rem;">
                            <h4>Class-wise Summary Report</h4>
                            <p>Academic Year: <?php echo date('Y') . '-' . (date('Y') + 1); ?></p>
                            <p>Total Classes: <?php echo count($class_summary); ?></p>
                        </div>
                        
                        <div class="table-container">
                            <table class="table" id="classReportTable">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Class Teacher</th>
                                        <th>Total Students</th>
                                        <th>Fee Collected (This Year)</th>
                                        <th>Academic Year</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($class_summary)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No classes found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($class_summary as $index => $class): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                <td><?php echo htmlspecialchars($class['teacher_name'] ?: 'Not Assigned'); ?></td>
                                                <td><?php echo $class['student_count']; ?></td>
                                                <td>₹<?php echo number_format($class['fee_collected'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
</body>
</html>
