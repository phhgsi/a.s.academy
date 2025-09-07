<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get expense ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: expenses_list.php');
    exit();
}

$expense_id = $_GET['id'];

// Get expense details
$stmt = $pdo->prepare("
    SELECT e.*, 
           u1.full_name as created_by_name,
           u2.full_name as approved_by_name
    FROM expenses e 
    LEFT JOIN users u1 ON e.created_by = u1.id 
    LEFT JOIN users u2 ON e.approved_by = u2.id
    WHERE e.id = ?
");
$stmt->execute([$expense_id]);
$expense = $stmt->fetch();

if (!$expense) {
    $_SESSION['success_message'] = 'Expense not found!';
    $_SESSION['success_type'] = 'error';
    header('Location: expenses_list.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expense - <?php echo htmlspecialchars($expense['voucher_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
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
                        <h1 class="page-title">Expense Details</h1>
                        <p class="page-subtitle"><?php echo htmlspecialchars($expense['voucher_no']); ?></p>
                    </div>
                    <div class="d-flex gap-1 align-center">
                        <a href="expenses_list.php" class="btn btn-secondary">← Back to List</a>
                        <a href="expenses_edit.php?id=<?php echo $expense['id']; ?>" class="btn btn-primary">✏️ Edit</a>
                        <button class="btn btn-outline" onclick="printSection('expenseDetails')">🖨️ Print</button>
                    </div>
                </div>

                <!-- Expense Details View -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Expense Information</h3>
                        <div class="d-flex gap-1">
                            <span class="badge badge-<?php echo $expense['category']; ?>"><?php echo htmlspecialchars($expense['category']); ?></span>
                        </div>
                    </div>
                    
                    <div id="expenseDetails">
                        <div style="padding: 2rem;">
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
        function printSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Expense Details - ${document.title}</title>
                            <link rel="stylesheet" href="../assets/css/print.css">
                            <style>
                                body { font-family: Arial, sans-serif; }
                                .card { border: 1px solid #ddd; margin: 20px; padding: 20px; }
                                .card-header { border-bottom: 2px solid #007bff; margin-bottom: 20px; padding-bottom: 10px; }
                            </style>
                        </head>
                        <body>
                            ${section.innerHTML}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }
    </script>
</body>
</html>
