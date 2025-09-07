<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Get current system settings
$stmt = $pdo->prepare("SELECT * FROM system_settings ORDER BY setting_key");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row;
}

// Handle settings update
if ($_POST['action'] ?? '' === 'update_settings') {
    try {
        foreach ($_POST['settings'] ?? [] as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $value]);
        }
        $success_message = 'Settings updated successfully.';
        
        // Refresh settings
        $stmt = $pdo->prepare("SELECT * FROM system_settings ORDER BY setting_key");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row;
        }
    } catch (Exception $e) {
        $error_message = 'Error updating settings: ' . $e->getMessage();
    }
}

// Get school info
$stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
$stmt->execute();
$school_info = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">System Settings</h1>
                    <p class="page-subtitle">Configure system-wide settings and preferences</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success mb-4">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- School Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">School Information</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($school_info): ?>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>School Name:</strong></td>
                                            <td><?php echo htmlspecialchars($school_info['school_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>School Code:</strong></td>
                                            <td><?php echo htmlspecialchars($school_info['school_code']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td><?php echo htmlspecialchars($school_info['phone']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($school_info['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Principal:</strong></td>
                                            <td><?php echo htmlspecialchars($school_info['principal_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Board:</strong></td>
                                            <td><?php echo htmlspecialchars($school_info['board']); ?></td>
                                        </tr>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">School information not configured.</p>
                                <?php endif; ?>
                                <a href="school_info.php" class="btn btn-primary">
                                    <i class="bi bi-gear me-2"></i>Edit School Info
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">System Configuration</h3>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_settings">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="current_academic_year" class="form-label">Current Academic Year</label>
                                        <input type="text" class="form-control" id="current_academic_year" name="settings[current_academic_year]" 
                                               value="<?php echo htmlspecialchars($settings['current_academic_year']['setting_value'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="school_session_start" class="form-label">Session Start Date (MM-DD)</label>
                                        <input type="text" class="form-control" id="school_session_start" name="settings[school_session_start]" 
                                               value="<?php echo htmlspecialchars($settings['school_session_start']['setting_value'] ?? '04-01'); ?>" placeholder="04-01">
                                    </div>

                                    <div class="mb-3">
                                        <label for="school_session_end" class="form-label">Session End Date (MM-DD)</label>
                                        <input type="text" class="form-control" id="school_session_end" name="settings[school_session_end]" 
                                               value="<?php echo htmlspecialchars($settings['school_session_end']['setting_value'] ?? '03-31'); ?>" placeholder="03-31">
                                    </div>

                                    <div class="mb-3">
                                        <label for="attendance_required_percentage" class="form-label">Required Attendance %</label>
                                        <input type="number" class="form-control" id="attendance_required_percentage" name="settings[attendance_required_percentage]" 
                                               value="<?php echo htmlspecialchars($settings['attendance_required_percentage']['setting_value'] ?? '75'); ?>" min="0" max="100">
                                    </div>

                                    <div class="mb-3">
                                        <label for="late_fee_per_day" class="form-label">Late Fee per Day (₹)</label>
                                        <input type="number" class="form-control" id="late_fee_per_day" name="settings[late_fee_per_day]" 
                                               value="<?php echo htmlspecialchars($settings['late_fee_per_day']['setting_value'] ?? '5'); ?>" min="0" step="0.01">
                                    </div>

                                    <div class="mb-3">
                                        <label for="school_currency" class="form-label">Currency</label>
                                        <select class="form-select" id="school_currency" name="settings[school_currency]">
                                            <option value="INR" <?php echo ($settings['school_currency']['setting_value'] ?? '') === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                                            <option value="USD" <?php echo ($settings['school_currency']['setting_value'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                            <option value="EUR" <?php echo ($settings['school_currency']['setting_value'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="session_timeout_minutes" class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" id="session_timeout_minutes" name="settings[session_timeout_minutes]" 
                                               value="<?php echo htmlspecialchars($settings['session_timeout_minutes']['setting_value'] ?? '60'); ?>" min="15" max="480">
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Save Settings
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Academic Years Management -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Academic Years Management</h3>
                        <a href="academic_years.php" class="btn btn-primary">
                            <i class="bi bi-calendar-plus me-2"></i>Manage Academic Years
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Manage academic years, set current year, and configure session dates.</p>
                        
                        <?php
                        // Get academic years
                        try {
                            $stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year_name DESC LIMIT 5");
                            $academic_years = $stmt->fetchAll();
                        } catch (Exception $e) {
                            $academic_years = [];
                        }
                        ?>
                        
                        <?php if (!empty($academic_years)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Current</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($academic_years as $year): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($year['year_name']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($year['start_date'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($year['end_date'])); ?></td>
                                                <td>
                                                    <?php if ($year['is_current']): ?>
                                                        <span class="badge bg-success">Current</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($year['is_active']): ?>
                                                        <span class="badge bg-primary">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No academic years configured.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
