<?php
require_once '../config/database.php';

// Check if user is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

// Get cashier information
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.email 
    FROM cashiers c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.user_id = ? AND c.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$cashier = $stmt->fetch();

if (!$cashier) {
    $error = 'Cashier profile not found. Please contact administrator.';
} else {
    // Get cashier statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_collections,
            SUM(amount_paid) as total_amount,
            COUNT(CASE WHEN DATE(payment_date) = CURDATE() THEN 1 END) as today_collections,
            SUM(CASE WHEN DATE(payment_date) = CURDATE() THEN amount_paid ELSE 0 END) as today_amount
        FROM fee_payments 
        WHERE cashier_id = ?
    ");
    $stmt->execute([$cashier['id']]);
    $stats = $stmt->fetch();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $employee_id = $_POST['employee_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $mobile_no = $_POST['mobile_no'];
    $emergency_contact = $_POST['emergency_contact'];
    $address = $_POST['address'];
    $shift = $_POST['shift'];
    $joining_date = $_POST['joining_date'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE cashiers 
            SET employee_id = ?, first_name = ?, last_name = ?, mobile_no = ?, 
                emergency_contact = ?, address = ?, shift = ?, joining_date = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$employee_id, $first_name, $last_name, $mobile_no, 
                       $emergency_contact, $address, $shift, $joining_date, $_SESSION['user_id']]);
        
        $success = 'Profile updated successfully!';
        
        // Refresh cashier data
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.email 
            FROM cashiers c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.user_id = ? AND c.is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cashier = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Cashier Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">💼 My Profile</h1>
                    <p class="page-subtitle">View and update your profile information</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($cashier): ?>
                    <!-- Performance Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 My Performance Statistics</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="stat-card">
                                <div class="stat-value" style="color: var(--primary-color);"><?php echo number_format($stats['total_collections']); ?></div>
                                <div class="stat-label">Total Collections</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" style="color: #28a745;">₹<?php echo number_format($stats['total_amount'], 2); ?></div>
                                <div class="stat-label">Total Amount</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" style="color: #007bff;"><?php echo number_format($stats['today_collections']); ?></div>
                                <div class="stat-label">Today's Collections</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" style="color: #17a2b8;">₹<?php echo number_format($stats['today_amount'], 2); ?></div>
                                <div class="stat-label">Today's Amount</div>
                            </div>
                        </div>
                    </div>

                    <!-- Cashier Profile Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                            <div class="card-actions">
                                <button type="button" onclick="toggleEdit()" class="btn btn-primary" id="editBtn">
                                    ✏️ Edit Profile
                                </button>
                                <button class="btn btn-outline" onclick="printSection('profileDetails')">🖨️ Print Profile</button>
                            </div>
                        </div>
                        
                        <div id="profileDetails">
                            <!-- View Mode -->
                            <div id="viewMode">
                                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
                                    <div class="text-center">
                                        <?php if ($cashier['photo']): ?>
                                            <img src="../uploads/photos/<?php echo $cashier['photo']; ?>" 
                                                 alt="Cashier Photo" class="photo-preview" style="width: 180px; height: 180px;">
                                        <?php else: ?>
                                            <div style="width: 180px; height: 180px; background: var(--primary-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 600;">
                                                <?php echo strtoupper(substr($cashier['first_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="margin-top: 1rem; padding: 1rem; background: var(--light-color); border-radius: var(--border-radius);">
                                            <div style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($cashier['employee_id']); ?>
                                            </div>
                                            <small>Employee ID</small>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                                            <?php echo htmlspecialchars($cashier['first_name'] . ' ' . $cashier['last_name']); ?>
                                        </h2>
                                        
                                        <!-- Basic Information -->
                                        <div class="mb-3">
                                            <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Basic Information</h4>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                                <div><strong>Name:</strong> <?php echo htmlspecialchars($cashier['first_name'] . ' ' . $cashier['last_name']); ?></div>
                                                <div><strong>Employee ID:</strong> <?php echo htmlspecialchars($cashier['employee_id']); ?></div>
                                                <div><strong>Shift:</strong> <?php echo ucfirst($cashier['shift'] ?: 'Not specified'); ?></div>
                                                <div><strong>Joining Date:</strong> <?php echo $cashier['joining_date'] ? date('d/m/Y', strtotime($cashier['joining_date'])) : 'Not specified'; ?></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Contact Information -->
                                        <div class="mb-3">
                                            <h4 style="color: var(--text-primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Contact Information</h4>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                                <div><strong>Mobile:</strong> <?php echo htmlspecialchars($cashier['mobile_no'] ?: 'Not provided'); ?></div>
                                                <div><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($cashier['emergency_contact'] ?: 'Not provided'); ?></div>
                                                <div><strong>Email:</strong> <?php echo htmlspecialchars($cashier['email']); ?></div>
                                                <div><strong>Username:</strong> <?php echo htmlspecialchars($cashier['username']); ?></div>
                                            </div>
                                            
                                            <?php if ($cashier['address']): ?>
                                                <div style="margin-top: 1rem;">
                                                    <strong>Address:</strong><br>
                                                    <div style="background: white; padding: 1rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); margin-top: 0.5rem;">
                                                        <?php echo nl2br(htmlspecialchars($cashier['address'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div id="editMode" style="display: none;">
                                <form method="POST">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                        <div>
                                            <label>Employee ID:</label>
                                            <input type="text" name="employee_id" class="form-control" 
                                                   value="<?php echo htmlspecialchars($cashier['employee_id']); ?>" required>
                                        </div>
                                        <div>
                                            <label>First Name:</label>
                                            <input type="text" name="first_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($cashier['first_name']); ?>" required>
                                        </div>
                                        <div>
                                            <label>Last Name:</label>
                                            <input type="text" name="last_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($cashier['last_name']); ?>" required>
                                        </div>
                                        <div>
                                            <label>Shift:</label>
                                            <select name="shift" class="form-control">
                                                <option value="">Select Shift</option>
                                                <option value="morning" <?php echo $cashier['shift'] === 'morning' ? 'selected' : ''; ?>>Morning</option>
                                                <option value="evening" <?php echo $cashier['shift'] === 'evening' ? 'selected' : ''; ?>>Evening</option>
                                                <option value="full_day" <?php echo $cashier['shift'] === 'full_day' ? 'selected' : ''; ?>>Full Day</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Joining Date:</label>
                                            <input type="date" name="joining_date" class="form-control" 
                                                   value="<?php echo $cashier['joining_date']; ?>">
                                        </div>
                                        <div>
                                            <label>Mobile Number:</label>
                                            <input type="tel" name="mobile_no" class="form-control" 
                                                   value="<?php echo htmlspecialchars($cashier['mobile_no']); ?>">
                                        </div>
                                        <div>
                                            <label>Emergency Contact:</label>
                                            <input type="tel" name="emergency_contact" class="form-control" 
                                                   value="<?php echo htmlspecialchars($cashier['emergency_contact']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 1rem;">
                                        <label>Address:</label>
                                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($cashier['address']); ?></textarea>
                                    </div>
                                    
                                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                        <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
                                        <button type="button" onclick="cancelEdit()" class="btn btn-secondary">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Collections -->
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT fp.*, s.first_name, s.last_name, s.admission_no, c.class_name, c.section
                        FROM fee_payments fp
                        LEFT JOIN students s ON fp.student_id = s.id
                        LEFT JOIN classes c ON s.class_id = c.id
                        WHERE fp.cashier_id = ?
                        ORDER BY fp.payment_date DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$cashier['id']]);
                    $recent_collections = $stmt->fetchAll();
                    ?>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">💰 Recent Collections</h3>
                            <a href="fees.php" class="btn btn-outline">View All</a>
                        </div>
                        
                        <?php if (empty($recent_collections)): ?>
                            <div class="alert alert-info">
                                No fee collections recorded yet.
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Receipt No</th>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_collections as $payment): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($payment['receipt_no']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                                    <br><small><?php echo htmlspecialchars($payment['admission_no']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['class_name'] . ' ' . $payment['section']); ?></td>
                                                <td>₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                                <td>
                                                    <span class="payment-mode-badge payment-<?php echo $payment['payment_mode']; ?>">
                                                        <?php echo ucfirst($payment['payment_mode']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚡ Quick Actions</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="fees.php" class="btn btn-primary">
                                💰 Collect Fees
                            </a>
                            <a href="reports.php" class="btn btn-success">
                                📊 Fee Reports
                            </a>
                            <a href="dashboard.php" class="btn btn-outline">
                                🏠 Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">ℹ️ Account Information</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div><strong>Account Created:</strong> <?php echo date('d/m/Y', strtotime($cashier['created_at'])); ?></div>
                            <div><strong>Last Updated:</strong> <?php echo date('d/m/Y H:i', strtotime($cashier['updated_at'])); ?></div>
                            <div><strong>Account Status:</strong> <span class="status-badge status-active">Active</span></div>
                            <div><strong>Role:</strong> <span class="role-badge">Fee Cashier</span></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <style>
    .stat-card {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .stat-label {
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
    }

    .payment-mode-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .payment-cash {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .payment-online {
        background-color: #cce7ff;
        color: #004085;
        border: 1px solid #99d3ff;
    }
    
    .payment-cheque {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .status-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .role-badge {
        background: var(--primary-color);
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .photo-preview {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: var(--border-radius);
        border: 3px solid var(--border-color);
    }

    @media (max-width: 768px) {
        #profileDetails > div > div:first-child {
            grid-column: 1 / -1;
        }
        
        #profileDetails > div {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }
    }
    </style>

    <script>
    function toggleEdit() {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');
        const editBtn = document.getElementById('editBtn');
        
        if (viewMode.style.display === 'none') {
            // Switch to view mode
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            editBtn.innerHTML = '✏️ Edit Profile';
        } else {
            // Switch to edit mode
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
            editBtn.innerHTML = '👁️ View Profile';
        }
    }

    function cancelEdit() {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');
        const editBtn = document.getElementById('editBtn');
        
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        editBtn.innerHTML = '✏️ Edit Profile';
    }
    </script>
</body>
</html>
