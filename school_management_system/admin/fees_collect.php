<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle form submissions
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
        
        // Verify student exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE id = ? AND is_active = 1");
        $stmt->execute([$_POST['student_id']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Invalid student selected");
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
        
        // Set success message and redirect
        $_SESSION['success_message'] = 'Fee payment recorded successfully! Receipt No: ' . $_POST['receipt_no'];
        $_SESSION['success_type'] = 'success';
        header('Location: fees_list.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $message = 'Error recording payment: ' . $e->getMessage();
    }
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
    <title>Collect Fee - Admin Panel</title>
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
                        <h1 class="page-title">Collect Fee Payment</h1>
                        <p class="page-subtitle">Record a new fee payment</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="fees_list.php" class="btn btn-secondary">← Back to List</a>
                        <a href="fees_structure.php" class="btn btn-outline">📊 Fee Structure</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

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
                            <a href="fees_list.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/modern-ui.js"></script>
    
    <script>
        // Student filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterClass = document.getElementById('filterClass');
            const filterVillage = document.getElementById('filterVillage');
            const studentSelect = document.getElementById('studentSelect');
            
            function loadStudents() {
                const classId = filterClass.value;
                const village = filterVillage.value;
                
                if (!classId && !village) {
                    studentSelect.innerHTML = '<option value="">Select filter criteria first</option>';
                    return;
                }
                
                // Build query parameters
                const params = new URLSearchParams();
                if (classId) params.append('class_id', classId);
                if (village) params.append('village', village);
                
                // Load students via AJAX
                fetch(`get_students.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        studentSelect.innerHTML = '<option value="">Select Student</option>';
                        data.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = `${student.first_name} ${student.last_name} (${student.admission_no})`;
                            studentSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                        studentSelect.innerHTML = '<option value="">Error loading students</option>';
                    });
            }
            
            filterClass.addEventListener('change', loadStudents);
            filterVillage.addEventListener('change', loadStudents);
        });
    </script>
</body>
</html>
