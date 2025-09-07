<?php
/**
 * Reusable Print Header Component
 * 
 * Generates a standardized print header with school logo, name, and address
 * for consistent print layouts across all pages.
 * 
 * @param string $report_title The title of the report/document
 * @param array $additional_info Optional additional header information
 */

// Include school logo helper if not already included
if (!function_exists('getSchoolLogo')) {
    require_once dirname(__FILE__) . '/school_logo.php';
}

// Get school information from database
if (!isset($school_info_for_print)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
        $stmt->execute();
        $school_info_for_print = $stmt->fetch();
    } catch (Exception $e) {
        $school_info_for_print = null;
    }
}

// Set default values if school info not available
$school_name = $school_info_for_print['school_name'] ?? 'A.S.ACADEMY';
$school_address = $school_info_for_print['address'] ?? 'LAHAR ROAD UMARI BHIND MADHYA PRADESH (477331)';
$school_phone = $school_info_for_print['phone'] ?? '';
$school_email = $school_info_for_print['email'] ?? '';

// Get report title from parameter or page context
$report_title = $report_title ?? $_GET['report_title'] ?? 'Document Report';
?>

<div class="print-header-component">
    <div class="school-info">
        <?php echo getSchoolLogo('xl', 'print-logo'); ?>
        <h1 class="school-name"><?php echo htmlspecialchars($school_name); ?></h1>
        <p class="school-address">
            <?php echo htmlspecialchars($school_address); ?>
            <?php if ($school_phone): ?>
                <br>Phone: <?php echo htmlspecialchars($school_phone); ?>
            <?php endif; ?>
            <?php if ($school_email): ?>
                | Email: <?php echo htmlspecialchars($school_email); ?>
            <?php endif; ?>
        </p>
        <h2 class="report-title"><?php echo htmlspecialchars($report_title); ?></h2>
        
        <?php if (isset($additional_info) && is_array($additional_info)): ?>
            <div class="additional-info">
                <?php foreach ($additional_info as $key => $value): ?>
                    <p><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Screen-only styles for the print header component */
@media screen {
    .print-header-component {
        display: none;
    }
}

@media print {
    .print-header-component .additional-info {
        margin-top: 8pt;
        font-size: 10pt;
        color: #333;
    }
    
    .print-header-component .additional-info p {
        margin: 2pt 0;
        display: inline-block;
        margin-right: 15pt;
    }
}
</style>
