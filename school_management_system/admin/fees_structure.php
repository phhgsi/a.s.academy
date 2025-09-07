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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_fee_structure'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Validate required fields
        $required_fields = ['class_id', 'fee_type', 'amount', 'academic_year'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Required field '{$field}' is missing");
            }
        }
        
        // Validate amount is positive
        if ($_POST['amount'] <= 0) {
            throw new Exception("Amount must be greater than 0");
        }
        
        // Check if fee structure already exists for this class and type
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_structure WHERE class_id = ? AND fee_type = ? AND academic_year = ? AND is_active = 1");
        $stmt->execute([$_POST['class_id'], $_POST['fee_type'], $_POST['academic_year']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Fee structure already exists for this class and fee type in the selected academic year");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO fee_structure (class_id, fee_type, amount, academic_year)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $_POST['class_id'], $_POST['fee_type'], $_POST['amount'], $_POST['academic_year']
        ]);
        
        if (!$result) {
            throw new Exception("Failed to add fee structure");
        }
        
        // Commit transaction
        $pdo->commit();
        $message = 'Fee structure added successfully!';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $message = 'Error adding fee structure: ' . $e->getMessage();
    }
}

// Get classes for dropdowns
$stmt = $pdo->prepare("SELECT * FROM classes WHERE is_active = 1 ORDER BY class_name");
$stmt->execute();
$classes = $stmt->fetchAll();

// Get fee structure
$stmt = $pdo->prepare("
    SELECT fs.*, c.class_name, c.section 
    FROM fee_structure fs 
    LEFT JOIN classes c ON fs.class_id = c.id 
    WHERE fs.is_active = 1 
    ORDER BY c.class_name, fs.fee_type
");
$stmt->execute();
$fee_structure = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Structure - Admin Panel</title>
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
                        <h1 class="page-title">Fee Structure Management</h1>
                        <p class="page-subtitle">Manage fee structure and pricing</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="fees_list.php" class="btn btn-secondary">← Back to List</a>
                        <a href="fees_collect.php" class="btn btn-outline">💰 Collect Fee</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Fee Structure Management -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <!-- Add Fee Structure Form -->
                    <div class="form-container">
                        <form method="POST">
                            <h3 style="margin-bottom: 2rem; color: var(--primary-color);">📊 Add Fee Structure</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Class *</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>">
                                            <?php echo $class['class_name'] . ' ' . $class['section']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Fee Type *</label>
                                <select name="fee_type" class="form-select" required>
                                    <option value="">Select Fee Type</option>
                                    <option value="Tuition Fee">Tuition Fee</option>
                                    <option value="Admission Fee">Admission Fee</option>
                                    <option value="Examination Fee">Examination Fee</option>
                                    <option value="Sports Fee">Sports Fee</option>
                                    <option value="Library Fee">Library Fee</option>
                                    <option value="Development Fee">Development Fee</option>
                                    <option value="Transport Fee">Transport Fee</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Amount *</label>
                                <input type="number" name="amount" class="form-input" required step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Academic Year *</label>
                                <select name="academic_year" class="form-select" required>
                                    <?php 
                                    $current_year = date('Y');
                                    for ($i = $current_year - 1; $i <= $current_year + 2; $i++): 
                                        $year_text = $i . '-' . ($i + 1);
                                    ?>
                                        <option value="<?php echo $year_text; ?>" <?php echo ($i == $current_year) ? 'selected' : ''; ?>>
                                            <?php echo $year_text; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="add_fee_structure" class="btn btn-primary">➕ Add Fee Structure</button>
                        </form>
                    </div>
                    
                    <!-- Current Fee Structure -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Current Fee Structure</h3>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Academic Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fee_structure)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No fee structure defined</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fee_structure as $fee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fee['class_name'] . ' ' . $fee['section']); ?></td>
                                                <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                                <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($fee['academic_year']); ?></td>
                                                <td>
                                                    <a href="fees_structure_edit.php?id=<?php echo $fee['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;">✏️</a>
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
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/modern-ui.js"></script>
</body>
</html>
