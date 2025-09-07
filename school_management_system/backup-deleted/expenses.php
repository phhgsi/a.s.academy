<?php
// Redirect to new separated expenses list page
header('Location: expenses_list.php');
exit();

$message = '';
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_expense'])) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Validate required fields (based on database schema NOT NULL constraints)
            $required_fields = ['voucher_no', 'amount', 'reason', 'expense_date'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    throw new Exception("Required field '{$field}' is missing or empty");
                }
            }
            
            // Validate amount is positive
            if ($_POST['amount'] <= 0) {
                throw new Exception("Amount must be greater than 0");
            }
            
            // Check if voucher number already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE voucher_no = ?");
            $stmt->execute([$_POST['voucher_no']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Voucher number already exists");
            }
            
            // Validate expense date is not in future
            if (strtotime($_POST['expense_date']) > time()) {
                throw new Exception("Expense date cannot be in the future");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO expenses (voucher_no, amount, reason, expense_date, category, approved_by, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $_POST['voucher_no'], $_POST['amount'], $_POST['reason'],
                $_POST['expense_date'], $_POST['category'], $_SESSION['user_id'], $_SESSION['user_id']
            ]);
            
            if (!$result) {
                throw new Exception("Failed to record expense");
            }
            
            // Commit transaction
            $pdo->commit();
            $message = 'Expense recorded successfully! Voucher No: ' . $_POST['voucher_no'];
            $action = 'list';
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            $message = 'Error recording expense: ' . $e->getMessage();
        }
    }
}

// Get expenses for listing
if ($action == 'list') {
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
}

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
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">➕ Add Expense</a>
                    <?php else: ?>
                        <a href="?" class="btn btn-secondary">← Back to List</a>
                    <?php endif; ?>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($action == 'list'): ?>
                    <!-- Monthly Summary -->
                    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 2rem;">
                        <div class="stat-card">
                            <div class="stat-header d-flex justify-between align-center">
                                <div class="stat-title">Current Month</div>
                                <div class="stat-icon" style="background: var(--danger-color);">💳</div>
                            </div>
                            <div class="stat-value">₹<?php echo number_format($summary['current_month'], 2); ?></div>
                            <div class="stat-change">Total expenses</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header d-flex justify-between align-center">
                                <div class="stat-title">Last Month</div>
                                <div class="stat-icon" style="background: var(--secondary-color);">📊</div>
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
                                <button class="btn btn-outline" onclick="exportToCSV('expensesTable', 'expenses_list')">📊 Export</button>
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
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
                                                        <a href="?action=view&id=<?php echo $expense['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;">👁️</a>
                                                        <a href="?action=edit&id=<?php echo $expense['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;">✏️</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($action == 'add'): ?>
                    <!-- Add Expense Form -->
                    <div class="form-container">
                        <form method="POST">
                            <h3 style="margin-bottom: 2rem; color: var(--primary-color);">➕ Add New Expense</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Voucher Number *</label>
                                    <input type="text" name="voucher_no" class="form-input" required readonly
                                           value="VCH<?php echo date('Ymd') . rand(1000, 9999); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Expense Date *</label>
                                    <input type="date" name="expense_date" class="form-input" required
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Category *</label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="Salaries">Salaries & Wages</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Maintenance">Maintenance & Repairs</option>
                                        <option value="Supplies">Office Supplies</option>
                                        <option value="Equipment">Equipment Purchase</option>
                                        <option value="Transportation">Transportation</option>
                                        <option value="Events">Events & Activities</option>
                                        <option value="Marketing">Marketing & Promotion</option>
                                        <option value="Legal">Legal & Professional</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Amount *</label>
                                    <input type="number" name="amount" class="form-input" required step="0.01" min="0" placeholder="0.00">
                                </div>
                                
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Reason/Description *</label>
                                    <textarea name="reason" class="form-textarea" required placeholder="Detailed description of the expense..."></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" name="add_expense" class="btn btn-primary">➕ Add Expense</button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action == 'view' && isset($_GET['id'])): ?>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT e.*, 
                               u1.full_name as created_by_name,
                               u2.full_name as approved_by_name
                        FROM expenses e 
                        LEFT JOIN users u1 ON e.created_by = u1.id 
                        LEFT JOIN users u2 ON e.approved_by = u2.id
                        WHERE e.id = ?
                    ");
                    $stmt->execute([$_GET['id']]);
                    $expense = $stmt->fetch();
                    ?>
                    
                    <?php if ($expense): ?>
                        <!-- Expense Details View -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Expense Details</h3>
                                <div class="d-flex gap-1">
                                    <a href="?action=edit&id=<?php echo $expense['id']; ?>" class="btn btn-primary">✏️ Edit</a>
                                    <button class="btn btn-outline" onclick="printSection('expenseDetails')">🖨️ Print</button>
                                </div>
                            </div>
                            
                            <div id="expenseDetails">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                                    <div><strong>Voucher Number:</strong> <?php echo htmlspecialchars($expense['voucher_no']); ?></div>
                                    <div><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></div>
                                    <div><strong>Category:</strong> <?php echo htmlspecialchars($expense['category']); ?></div>
                                    <div><strong>Amount:</strong> ₹<?php echo number_format($expense['amount'], 2); ?></div>
                                    <div><strong>Created By:</strong> <?php echo htmlspecialchars($expense['created_by_name']); ?></div>
                                    <div><strong>Approved By:</strong> <?php echo htmlspecialchars($expense['approved_by_name']); ?></div>
                                    <div><strong>Created On:</strong> <?php echo date('d/m/Y H:i', strtotime($expense['created_at'])); ?></div>
                                </div>
                                
                                <div style="margin-top: 2rem;">
                                    <strong>Reason/Description:</strong><br>
                                    <div style="background: var(--light-color); padding: 1rem; border-radius: var(--border-radius); margin-top: 0.5rem;">
                                        <?php echo nl2br(htmlspecialchars($expense['reason'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Initialize page-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-generate voucher number
            fillVoucherNumber();
        });
    </script>
</body>
</html>
