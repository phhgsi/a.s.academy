<?php
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'cashier'])) {
    header('Location: ../login.php');
    exit();
}

$receipt_id = $_GET['id'] ?? '';

if (!$receipt_id) {
    header('Location: fees.php');
    exit();
}

// Get fee payment details
$stmt = $pdo->prepare("
    SELECT fp.*, s.first_name, s.last_name, s.admission_no, s.father_name, s.mother_name,
           c.class_name, c.section, c.academic_year,
           u.full_name as collected_by
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON fp.collected_by = u.id
    WHERE fp.id = ?
");
$stmt->execute([$receipt_id]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: fees.php');
    exit();
}

// Convert amount to words
function numberToWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'forty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        return false;
    }

    if ($number < 0) {
        return $negative . numberToWords(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number, 2);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . numberToWords($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $digit) {
            $words[] = $dictionary[$digit];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}

$amount_in_words = ucfirst(numberToWords($payment['amount'])) . ' rupees only';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - <?php echo htmlspecialchars($payment['receipt_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 0;
        }
        
        .receipt-number {
            background: #000;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }
        
        .print-body {
            padding: 30px;
        }
        
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h4 {
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
            color: #333;
            font-size: 16px;
        }
        
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 130px;
            color: #333;
        }
        
        .info-value {
            flex: 1;
            color: #666;
        }
        
        .amount-section {
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        
        .amount-paid {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        
        .amount-words {
            font-style: italic;
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .payment-detail {
            text-align: center;
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .payment-detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .payment-detail-value {
            font-weight: bold;
            color: #333;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 50px;
            padding-top: 30px;
        }
        
        .signature-box {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        
        @media screen {
            .print-controls {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
            }
            
            .print-controls button {
                margin-left: 10px;
                padding: 10px 20px;
                background: #3b82f6;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
            }
            
            .print-controls button:hover {
                background: #2563eb;
            }
        }
        
        @media print {
            .receipt-container {
                border: 3px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Print Receipt</button>
        <button onclick="window.close()">❌ Close</button>
    </div>

    <div class="receipt-container">
        <?php include 'print_header.php'; ?>
        
        <div class="receipt-number">Receipt No: <?php echo htmlspecialchars($payment['receipt_no']); ?></div>
        
        <main class="print-body">
            <div class="receipt-info">
                <div class="info-section">
                    <h4>Student Details</h4>
                    <div class="info-row">
                        <span class="info-label">Student Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Admission No:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['admission_no']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Class:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['class_name'] . ' - ' . $payment['section']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Father's Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['father_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Academic Year:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['academic_year']); ?></span>
                    </div>
                </div>

                <div class="info-section">
                    <h4>Payment Details</h4>
                    <div class="info-row">
                        <span class="info-label">Fee Type:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['fee_type']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Date:</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value"><?php echo ucfirst($payment['payment_method']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Transaction ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['transaction_id'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Collected By:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['collected_by'] ?: 'System'); ?></span>
                    </div>
                </div>
            </div>

            <div class="amount-section">
                <div class="amount-paid">₹<?php echo number_format($payment['amount'], 2); ?></div>
                <div class="amount-words"><?php echo $amount_in_words; ?></div>
                
                <div class="payment-details">
                    <div class="payment-detail">
                        <div class="payment-detail-label">Receipt Date</div>
                        <div class="payment-detail-value"><?php echo date('d/m/Y', strtotime($payment['created_at'])); ?></div>
                    </div>
                    <div class="payment-detail">
                        <div class="payment-detail-label">Payment Status</div>
                        <div class="payment-detail-value" style="color: green; font-weight: bold;">PAID</div>
                    </div>
                    <div class="payment-detail">
                        <div class="payment-detail-label">Receipt Status</div>
                        <div class="payment-detail-value" style="color: blue; font-weight: bold;">ORIGINAL</div>
                    </div>
                </div>
            </div>

            <?php if ($payment['remarks']): ?>
            <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #3b82f6;">
                <strong>Remarks:</strong> <?php echo htmlspecialchars($payment['remarks']); ?>
            </div>
            <?php endif; ?>

            <div class="signature-section">
                <div class="signature-box">
                    <strong>Student/Parent Signature</strong>
                </div>
                <div class="signature-box">
                    <strong>Cashier Signature</strong>
                </div>
                <div class="signature-box">
                    <strong>Principal Signature</strong>
                </div>
            </div>

        </main>
        
        <footer class="print-footer">
            <p><strong>Note:</strong> This is a computer generated receipt. Please keep this receipt for your records.</p>
            <p>For any queries regarding this receipt, please contact the school office.</p>
            <p><strong>Generated on:</strong> <?php echo date('d/m/Y H:i:s'); ?> | © 2024 A.S.Academy Higher Secondary School. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Print automatically when page loads
        window.addEventListener('load', function() {
            // Auto-print after a short delay to ensure content is loaded
            setTimeout(function() {
                window.print();
            }, 500);
        });
        
        // Close window after printing (for popup windows)
        window.addEventListener('afterprint', function() {
            if (window.opener) {
                window.close();
            }
        });
    </script>
</body>
</html>
