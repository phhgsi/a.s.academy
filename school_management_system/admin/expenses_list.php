<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle flash messages from session
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = $_SESSION['success_type'] ?? 'success';
    unset($_SESSION['success_message']);
    unset($_SESSION['success_type']);
}

// Get expenses for listing
$stmt = $pdo->prepare("
    SELECT e.*, 
           u1.full_name as created_by_name,
           u2.full_name as approved_by_name
    FROM expenses e 
    LEFT JOIN users u1 ON e.created_by = u1.id 
    LEFT JOIN users u2 ON e.approved_by = u2.id
    ORDER BY e.expense_date DESC, e.created_at DESC
");
$stmt->execute();
$expenses = $stmt->fetchAll();

// Calculate monthly summary
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as current_month,
        COALESCE(SUM(CASE WHEN MONTH(expense_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
                          AND YEAR(expense_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
                     THEN amount ELSE 0 END), 0) as last_month
    FROM expenses 
    WHERE MONTH(expense_date) IN (MONTH(CURRENT_DATE()), MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)))
    AND YEAR(expense_date) >= YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
");
$stmt->execute();
$summary = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses Management - Admin Panel</title>
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
                <div class="page-header d-flex justify-between align-center">
                    <div>
                        <h1 class="page-title">Expenses Management</h1>
                        <p class="page-subtitle">Track and manage school expenses</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="expenses_add.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Expense</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Monthly Summary -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Current Month</div>
                            <div class="stat-icon" style="background: var(--danger-color);"><i class="bi bi-credit-card"></i></div>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($summary['current_month'], 2); ?></div>
                        <div class="stat-change">Total expenses</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Last Month</div>
                            <div class="stat-icon" style="background: var(--secondary-color);"><i class="bi bi-bar-chart"></i></div>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($summary['last_month'], 2); ?></div>
                        <?php 
                        $change = $summary['current_month'] - $summary['last_month'];
                        $change_class = $change > 0 ? 'negative' : 'positive';
                        $change_text = $change > 0 ? '+₹' . number_format(abs($change), 2) . ' increase' : '-₹' . number_format(abs($change), 2) . ' decrease';
                        ?>
                        <div class="stat-change <?php echo $change_class; ?>"><?php echo $change_text; ?></div>
                    </div>
                </div>

                <!-- Expenses List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Expenses</h3>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline" onclick="exportToCSV('expensesTable', 'expenses_list')"><i class="bi bi-file-earmark-spreadsheet"></i> Export</button>
                            <button class="btn btn-outline" onclick="printReport()"><i class="bi bi-printer"></i> Print</button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table data-table" id="expensesTable">
                            <thead>
                                <tr>
                                    <th>Voucher No</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Reason</th>
                                    <th>Amount</th>
                                    <th>Created By</th>
                                    <th>Approved By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No expenses found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['voucher_no']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo htmlspecialchars($expense['category']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($expense['reason']); ?></td>
                                            <td>₹<?php echo number_format($expense['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($expense['created_by_name']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['approved_by_name']); ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="expenses_view.php?id=<?php echo $expense['id']; ?>" class="btn btn-outline btn-sm" title="View Expense">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="expenses_edit.php?id=<?php echo $expense['id']; ?>" class="btn btn-outline btn-sm" title="Edit Expense">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
        function exportToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let row of rows) {
                const cols = row.querySelectorAll('td, th');
                const csvRow = [];
                for (let col of cols) {
                    csvRow.push('"' + col.textContent.replace(/"/g, '""') + '"');
                }
                csv.push(csvRow.join(','));
            }
            
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
