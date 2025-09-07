<?php
require_once '../config/database.php';

// Check if user is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$action = $_GET['action'] ?? 'list';

// Handle fee collection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['collect_fee'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Validate required fields (based on database schema NOT NULL constraints)
        $required_fields = ['receipt_no', 'student_id', 'amount', 'payment_date'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Required field '{$field}' is missing or empty");
            }
        }
        
        // Validate amount is positive
        if ($_POST['amount'] <= 0) {
            throw new Exception("Amount must be greater than 0");
        }
        
        // Check if receipt number already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE receipt_no = ?");
        $stmt->execute([$_POST['receipt_no']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Receipt number already exists");
        }
        
        // Verify student exists and is active
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE id = ? AND is_active = 1");
        $stmt->execute([$_POST['student_id']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Selected student not found or inactive");
        }
        
        // Validate payment date is not in future
        if (strtotime($_POST['payment_date']) > time()) {
            throw new Exception("Payment date cannot be in the future");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO fee_payments (receipt_no, student_id, amount, payment_method, payment_date, academic_year, fee_type, remarks, collected_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $_POST['receipt_no'], $_POST['student_id'], $_POST['amount'],
            $_POST['payment_method'], $_POST['payment_date'], $_POST['academic_year'],
            $_POST['fee_type'], $_POST['remarks'], $_SESSION['user_id']
        ]);
        
        if (!$result) {
            throw new Exception("Failed to record fee payment");
        }
        
        // Commit transaction
        $pdo->commit();
        $message = 'Fee payment recorded successfully! Receipt No: ' . $_POST['receipt_no'];
        $action = 'list';
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $message = 'Error recording payment: ' . $e->getMessage();
    }
}

// Get fee payments (only by this cashier for view)
if ($action == 'list') {
    $stmt = $pdo->prepare("
        SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name 
        FROM fee_payments fp 
        JOIN students s ON fp.student_id = s.id 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE fp.collected_by = ?
        ORDER BY fp.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $fee_payments = $stmt->fetchAll();
}

// Get classes for dropdowns
$stmt = $pdo->prepare("SELECT * FROM classes WHERE is_active = 1 ORDER BY class_name");
$stmt->execute();
$classes = $stmt->fetchAll();

// Get villages for filtering
$stmt = $pdo->prepare("SELECT DISTINCT village FROM students WHERE is_active = 1 AND village IS NOT NULL ORDER BY village");
$stmt->execute();
$villages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Collection - Cashier Panel</title>
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
                        <h1 class="page-title">Fee Collection</h1>
                        <p class="page-subtitle">Collect student fees and manage receipts</p>
                    </div>
                    <?php if ($action == 'list'): ?>
                        <a href="?action=collect" class="btn btn-primary">💰 Collect Fee</a>
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
                    <!-- Fee Payments List (Only collected by this cashier) -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">My Fee Collections</h3>
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline" onclick="exportToCSV('paymentsTable', 'my_fee_collections')">📊 Export</button>
                                <button class="btn btn-outline" onclick="printReport()">🖨️ Print</button>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fee_payments)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No fee payments collected yet</td>
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
                                                <td>
                                                    <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-outline" style="padding: 0.5rem;" target="_blank">🧾 Receipt</a>
                                                    <!-- Note: No delete option for cashier -->
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($action == 'collect'): ?>
                    <!-- Fee Collection Form -->
                    <div class="form-container">
                        <form method="POST">
                            <h3 style="margin-bottom: 2rem; color: var(--primary-color);">💰 Collect Fee Payment</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Receipt Number *</label>
                                    <input type="text" name="receipt_no" class="form-input" required readonly
                                           value="RCP<?php echo date('Ymd') . rand(1000, 9999); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Payment Date *</label>
                                    <input type="date" name="payment_date" class="form-input" required
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Filter by Class</label>
                                    <select name="filter_class" class="form-select" id="filterClass">
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
                                    <select name="filter_village" class="form-select" id="filterVillage">
                                        <option value="">All Villages</option>
                                        <?php foreach ($villages as $village): ?>
                                            <option value="<?php echo $village['village']; ?>">
                                                <?php echo htmlspecialchars($village['village']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Select Student *</label>
                                    <select name="student_id" class="form-select" required id="studentSelect">
                                        <option value="">Select filter criteria first</option>
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
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Amount *</label>
                                    <input type="number" name="amount" class="form-input" required step="0.01" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Payment Method *</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="online">Online</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="dd">Demand Draft</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Academic Year *</label>
                                    <select name="academic_year" class="form-select" required>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($i = $current_year - 2; $i <= $current_year + 1; $i++): 
                                            $year_text = $i . '-' . ($i + 1);
                                        ?>
                                            <option value="<?php echo $year_text; ?>" <?php echo ($i == $current_year) ? 'selected' : ''; ?>>
                                                <?php echo $year_text; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="remarks" class="form-textarea" placeholder="Any additional notes..."></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" name="collect_fee" class="btn btn-primary">💰 Record Payment</button>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>

                    <!-- Collection Summary for Today -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Today's Collection Summary</h3>
                        </div>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as receipt_count,
                                COALESCE(SUM(amount), 0) as total_amount,
                                payment_method,
                                COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_count,
                                COUNT(CASE WHEN payment_method = 'online' THEN 1 END) as online_count,
                                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN amount END), 0) as cash_total,
                                COALESCE(SUM(CASE WHEN payment_method = 'online' THEN amount END), 0) as online_total
                            FROM fee_payments 
                            WHERE collected_by = ? AND DATE(payment_date) = CURRENT_DATE()
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $today_summary = $stmt->fetch();
                        ?>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 1rem;">
                            <div class="text-center">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo $today_summary['receipt_count']; ?>
                                </div>
                                <small>Total Receipts</small>
                            </div>
                            <div class="text-center">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-color);">
                                    ₹<?php echo number_format($today_summary['total_amount'], 2); ?>
                                </div>
                                <small>Total Collection</small>
                            </div>
                            <div class="text-center">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning-color);">
                                    ₹<?php echo number_format($today_summary['cash_total'], 2); ?>
                                </div>
                                <small>Cash (<?php echo $today_summary['cash_count']; ?>)</small>
                            </div>
                            <div class="text-center">
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary-color);">
                                    ₹<?php echo number_format($today_summary['online_total'], 2); ?>
                                </div>
                                <small>Online (<?php echo $today_summary['online_count']; ?>)</small>
                            </div>
                        </div>
                    </div>
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
            // Setup student filtering by class and village
            setupStudentFiltering();
            
            // Auto-generate receipt number
            fillReceiptNumber();
        });

        function setupStudentFiltering() {
            const filterClass = document.getElementById('filterClass');
            const filterVillage = document.getElementById('filterVillage');
            const studentSelect = document.getElementById('studentSelect');
            
            if (filterClass && filterVillage && studentSelect) {
                filterClass.addEventListener('change', loadStudents);
                filterVillage.addEventListener('change', loadStudents);
            }
        }
        
        function loadStudents() {
            const classId = document.getElementById('filterClass').value;
            const village = document.getElementById('filterVillage').value;
            const studentSelect = document.getElementById('studentSelect');
            
            if (!classId && !village) {
                studentSelect.innerHTML = '<option value="">Select filter criteria first</option>';
                return;
            }
            
            let url = '../admin/get_students.php?';
            if (classId) url += 'class_id=' + classId + '&';
            if (village) url += 'village=' + encodeURIComponent(village) + '&';
            
            fetch(url)
                .then(response => response.json())
                .then(students => {
                    studentSelect.innerHTML = '<option value="">Select Student</option>';
                    students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = `${student.admission_no} - ${student.first_name} ${student.last_name}`;
                        studentSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching students:', error);
                    studentSelect.innerHTML = '<option value="">Error loading students</option>';
                });
        }
    </script>
</body>
</html>
