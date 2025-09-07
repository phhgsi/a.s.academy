<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Get expense ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: expenses_list.php');
    exit();
}

$expense_id = $_GET['id'];

// Get expense details
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch();

if (!$expense) {
    $_SESSION['success_message'] = 'Expense not found!';
    $_SESSION['success_type'] = 'error';
    header('Location: expenses_list.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_expense'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Validate required fields
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
        
        // Check if voucher number already exists (exclude current record)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE voucher_no = ? AND id != ?");
        $stmt->execute([$_POST['voucher_no'], $expense_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Voucher number already exists");
        }
        
        // Validate expense date is not in future
        if (strtotime($_POST['expense_date']) > time()) {
            throw new Exception("Expense date cannot be in the future");
        }
        
        $stmt = $pdo->prepare("
            UPDATE expenses SET 
                voucher_no = ?, amount = ?, reason = ?, expense_date = ?, category = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $_POST['voucher_no'], $_POST['amount'], $_POST['reason'],
            $_POST['expense_date'], $_POST['category'], $expense_id
        ]);
        
        if (!$result) {
            throw new Exception("Failed to update expense");
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Set success message and redirect
        $_SESSION['success_message'] = 'Expense updated successfully!';
        $_SESSION['success_type'] = 'success';
        header('Location: expenses_list.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $message = 'Error updating expense: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense - Admin Panel</title>
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
                        <h1 class="page-title">Edit Expense</h1>
                        <p class="page-subtitle">Update expense information</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="expenses_list.php" class="btn btn-secondary">← Back to List</a>
                        <a href="expenses_view.php?id=<?php echo $expense['id']; ?>" class="btn btn-outline">👁️ View Details</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Expense Form -->
                <div class="form-container">
                    <form method="POST">
                        <h3 style="margin-bottom: 2rem; color: var(--primary-color);">
                            ✏️ Edit Expense: <?php echo htmlspecialchars($expense['voucher_no']); ?>
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Voucher Number *</label>
                                <input type="text" name="voucher_no" class="form-input" required
                                       value="<?php echo htmlspecialchars($expense['voucher_no']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Expense Date *</label>
                                <input type="date" name="expense_date" class="form-input" required
                                       value="<?php echo $expense['expense_date']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories = [
                                        'Salaries' => 'Salaries & Wages',
                                        'Utilities' => 'Utilities',
                                        'Maintenance' => 'Maintenance & Repairs',
                                        'Supplies' => 'Office Supplies',
                                        'Equipment' => 'Equipment Purchase',
                                        'Transportation' => 'Transportation',
                                        'Events' => 'Events & Activities',
                                        'Marketing' => 'Marketing & Promotion',
                                        'Legal' => 'Legal & Professional',
                                        'Other' => 'Other'
                                    ];
                                    foreach ($categories as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($expense['category'] == $key) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Amount *</label>
                                <input type="number" name="amount" class="form-input" required step="0.01" min="0" 
                                       value="<?php echo $expense['amount']; ?>">
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Reason/Description *</label>
                                <textarea name="reason" class="form-textarea" required><?php echo htmlspecialchars($expense['reason']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" name="update_expense" class="btn btn-primary">✏️ Update Expense</button>
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
</body>
</html>
