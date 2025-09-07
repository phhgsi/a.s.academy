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

// Get fee payments for listing
$stmt = $pdo->prepare("
    SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name, u.full_name as collected_by_name
    FROM fee_payments fp 
    JOIN students s ON fp.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON fp.collected_by = u.id
    ORDER BY fp.created_at DESC
");
$stmt->execute();
$fee_payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Management - Admin Panel</title>
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
                        <h1 class="page-title">Fees Management</h1>
                        <p class="page-subtitle">Manage fee collection and structure</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="fees_collect.php" class="btn btn-primary"><i class="bi bi-cash-coin"></i> Collect Fee</a>
                        <a href="fees_structure.php" class="btn btn-secondary"><i class="bi bi-table"></i> Fee Structure</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Fee Payments List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Fee Payment Records</h3>
                        <div class="d-flex gap-1">
                            <div class="export-btn-group">
                                <button class="btn btn-outline export-btn" data-format="csv" data-page="fees_export">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                </button>
                                <button class="btn btn-outline export-btn" data-format="pdf" data-page="fees_export">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </button>
                            </div>
                            <button class="btn btn-outline" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table data-table" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Student Details</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Fee Type</th>
                                    <th>Payment Date</th>
                                    <th>Collected By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fee_payments)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No fee payments found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($fee_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br>
                                                <small class="text-secondary">
                                                    <?php echo htmlspecialchars($payment['admission_no']); ?> | 
                                                    <?php echo htmlspecialchars($payment['class_name']); ?>
                                                </small>
                                            </td>
                                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $payment['payment_method'] == 'cash' ? 'warning' : 'success'; ?>">
                                                    <?php echo ucfirst($payment['payment_method']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['collected_by_name']); ?></td>
                                            <td>
                                <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-outline btn-sm" title="View Receipt" target="_blank">
                                    <i class="bi bi-receipt"></i>
                                </a>
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

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/modern-ui.js"></script>
</body>
</html>
