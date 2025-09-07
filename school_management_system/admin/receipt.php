<?php
require_once '../config/database.php';

// Check if user is logged in and has appropriate access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'cashier'])) {
    header('Location: ../login.php');
    exit();
}

$payment_id = $_GET['id'] ?? 0;

// Get payment details
$stmt = $pdo->prepare("
    SELECT fp.*, s.*, c.class_name, c.section, u.full_name as collected_by_name, si.school_name, si.address as school_address, si.phone as school_phone
    FROM fee_payments fp 
    JOIN students s ON fp.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON fp.collected_by = u.id
    CROSS JOIN school_info si
    WHERE fp.id = ? AND si.id = 1
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Receipt not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - <?php echo $payment['receipt_no']; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
            .receipt-container { box-shadow: none !important; border: 1px solid #000 !important; }
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }
        
        .receipt-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .receipt-body {
            padding: 2rem;
        }
        
        .receipt-footer {
            background: var(--light-color);
            padding: 1rem 2rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dotted var(--border-color);
        }
        
        .receipt-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin: 1rem;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">❌ Close</button>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            <h1><?php echo htmlspecialchars($payment['school_name']); ?></h1>
            <p><?php echo htmlspecialchars($payment['school_address']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($payment['school_phone']); ?></p>
            <h2 style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 1rem;">FEE RECEIPT</h2>
        </div>
        
        <div class="receipt-body">
            <!-- Receipt Details -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <strong>Receipt No:</strong> <?php echo htmlspecialchars($payment['receipt_no']); ?><br>
                    <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?><br>
                    <strong>Time:</strong> <?php echo date('h:i A', strtotime($payment['created_at'])); ?>
                </div>
                <div>
                    <strong>Academic Year:</strong> <?php echo htmlspecialchars($payment['academic_year']); ?><br>
                    <strong>Payment Method:</strong> <?php echo ucfirst($payment['payment_method']); ?><br>
                    <strong>Collected By:</strong> <?php echo htmlspecialchars($payment['collected_by_name']); ?>
                </div>
            </div>
            
            <!-- Student Details -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.5rem; margin-bottom: 2rem; background: var(--light-color);">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Student Details</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div><strong>Admission No:</strong> <?php echo htmlspecialchars($payment['admission_no']); ?></div>
                    <div><strong>Student Name:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                    <div><strong>Father's Name:</strong> <?php echo htmlspecialchars($payment['father_name']); ?></div>
                    <div><strong>Class:</strong> <?php echo htmlspecialchars($payment['class_name'] . ' ' . $payment['section']); ?></div>
                    <div><strong>Mobile:</strong> <?php echo htmlspecialchars($payment['parent_mobile']); ?></div>
                    <div><strong>Village:</strong> <?php echo htmlspecialchars($payment['village']); ?></div>
                </div>
            </div>
            
            <!-- Payment Details -->
            <div style="border: 2px solid var(--primary-color); border-radius: var(--border-radius); padding: 1.5rem;">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Payment Details</h4>
                
                <div class="receipt-row">
                    <span>Fee Type:</span>
                    <span><?php echo htmlspecialchars($payment['fee_type']); ?></span>
                </div>
                
                <div class="receipt-row">
                    <span>Amount Paid:</span>
                    <span>₹<?php echo number_format($payment['amount'], 2); ?></span>
                </div>
                
                <?php if ($payment['remarks']): ?>
                <div class="receipt-row">
                    <span>Remarks:</span>
                    <span><?php echo htmlspecialchars($payment['remarks']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="receipt-row" style="font-size: 1.2rem; color: var(--success-color); margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--primary-color);">
                    <span><strong>Total Amount Paid:</strong></span>
                    <span><strong>₹<?php echo number_format($payment['amount'], 2); ?></strong></span>
                </div>
            </div>
            
            <!-- Amount in Words -->
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--light-color); border-radius: var(--border-radius);">
                <strong>Amount in Words:</strong> 
                <?php 
                // Simple number to words conversion (you can enhance this)
                $amount_words = "Rupees " . ucwords(strtolower(number_format($payment['amount'], 2))) . " Only";
                echo $amount_words;
                ?>
            </div>
            
            <!-- Signatures -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-top: 3rem; text-align: center;">
                <div style="border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    <small>Student/Parent Signature</small>
                </div>
                <div style="border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    <small>Cashier Signature</small>
                </div>
                <div style="border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                    <small>Authorized Signature</small>
                </div>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p>This is a computer-generated receipt. Please keep it safe for your records.</p>
            <p>Generated on: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
