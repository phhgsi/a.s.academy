<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
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
        
        // Set success message and redirect
        $_SESSION['success_message'] = 'Expense recorded successfully! Voucher No: ' . $_POST['voucher_no'];
        $_SESSION['success_type'] = 'success';
        header('Location: expenses_list.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $message = 'Error recording expense: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Admin Panel</title>
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
                        <h1 class="page-title">Add New Expense</h1>
                        <p class="page-subtitle">Record a new expense entry</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="expenses_list.php" class="btn btn-secondary">← Back to List</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

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
                            <a href="expenses_list.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
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
        // Initialize page-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-generate voucher number
            fillVoucherNumber();
        });
        
        function fillVoucherNumber() {
            // Voucher number is already generated in PHP, this is just a placeholder function
            console.log('Voucher number generated');
        }
    </script>
</body>
</html>
