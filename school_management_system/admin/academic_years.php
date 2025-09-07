<?php
require_once '../config/database.php';
require_once '../includes/academic_year.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_academic_year'])) {
        try {
            $academic_year = $_POST['academic_year'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // If this is being set as active, deactivate all others
            if ($is_active) {
                $stmt = $pdo->prepare("UPDATE academic_years SET is_active = 0");
                $stmt->execute();
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO academic_years (academic_year, start_date, end_date, is_active, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$academic_year, $start_date, $end_date, $is_active]);
            
            if ($is_active) {
                setCurrentAcademicYear($academic_year);
            }
            
            $message = 'Academic year added successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $message = 'Error adding academic year: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_academic_year'])) {
        try {
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // If this is being set as active, deactivate all others
            if ($is_active) {
                $stmt = $pdo->prepare("UPDATE academic_years SET is_active = 0");
                $stmt->execute();
            }
            
            $stmt = $pdo->prepare("
                UPDATE academic_years SET 
                    start_date = ?, 
                    end_date = ?, 
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['start_date'], 
                $_POST['end_date'], 
                $is_active, 
                $_POST['academic_year_id']
            ]);
            
            if ($is_active) {
                $stmt = $pdo->prepare("SELECT academic_year FROM academic_years WHERE id = ?");
                $stmt->execute([$_POST['academic_year_id']]);
                $academic_year = $stmt->fetchColumn();
                setCurrentAcademicYear($academic_year);
            }
            
            $message = 'Academic year updated successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $message = 'Error updating academic year: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['set_current_year'])) {
        try {
            $academic_year_id = $_POST['academic_year_id'];
            
            // Get the academic year
            $stmt = $pdo->prepare("SELECT academic_year FROM academic_years WHERE id = ?");
            $stmt->execute([$academic_year_id]);
            $academic_year = $stmt->fetchColumn();
            
            if ($academic_year) {
                // Deactivate all others
                $stmt = $pdo->prepare("UPDATE academic_years SET is_active = 0");
                $stmt->execute();
                
                // Activate selected year
                $stmt = $pdo->prepare("UPDATE academic_years SET is_active = 1 WHERE id = ?");
                $stmt->execute([$academic_year_id]);
                
                // Set as current
                setCurrentAcademicYear($academic_year);
                
                $message = 'Current academic year set to: ' . $academic_year;
            }
        } catch (Exception $e) {
            $message = 'Error setting current academic year: ' . $e->getMessage();
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete']) {
    try {
        // Check if this academic year has data
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE academic_year = (SELECT academic_year FROM academic_years WHERE id = ?)");
        $stmt->execute([$_GET['delete']]);
        $student_count = $stmt->fetchColumn();
        
        if ($student_count > 0) {
            $message = 'Cannot delete academic year with existing student data. Please move or delete students first.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM academic_years WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $message = 'Academic year deleted successfully!';
        }
    } catch (Exception $e) {
        $message = 'Error deleting academic year: ' . $e->getMessage();
    }
}

// Get all academic years
if ($action == 'list') {
    try {
        $stmt = $pdo->prepare("
            SELECT ay.*, 
                   (SELECT COUNT(*) FROM students s WHERE s.academic_year = ay.academic_year) as student_count,
                   (SELECT COUNT(*) FROM fee_structure fs WHERE fs.academic_year = ay.academic_year) as fee_structure_count
            FROM academic_years ay 
            ORDER BY ay.academic_year DESC
        ");
        $stmt->execute();
        $academic_years = $stmt->fetchAll();
    } catch (Exception $e) {
        $academic_years = [];
    }
}

// Get single academic year for editing
if ($action == 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM academic_years WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $academic_year_data = $stmt->fetch();
        
        if (!$academic_year_data) {
            $action = 'list';
            $message = 'Academic year not found!';
        }
    } catch (Exception $e) {
        $action = 'list';
        $message = 'Error loading academic year: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Years Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">Academic Years Management</h1>
                        <p class="page-subtitle">Manage academic years and set current active year</p>
                    </div>
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Add Academic Year
                        </a>
                    <?php else: ?>
                        <a href="?" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <i class="bi bi-<?php echo strpos($message, 'Error') !== false ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($action == 'list'): ?>
                    <!-- Current Academic Year Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-calendar-star me-2"></i>Current Academic Year
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo getCurrentAcademicYear(); ?></h3>
                                    <p class="text-muted mb-0">All data is currently filtered by this academic year</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-info-circle me-2"></i>Quick Info
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Total Academic Years:</strong> <?php echo count($academic_years); ?></p>
                                    <p><strong>Students in Current Year:</strong> 
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE academic_year = ? AND is_active = 1");
                                            $stmt->execute([getCurrentAcademicYear()]);
                                            echo $stmt->fetchColumn();
                                        } catch (Exception $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Years List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">All Academic Years</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover data-table">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Students</th>
                                            <th>Fee Structures</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($academic_years)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No academic years found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($academic_years as $year): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($year['academic_year']); ?></strong>
                                                        <?php if ($year['is_active']): ?>
                                                            <span class="badge bg-success ms-2">Current</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($year['start_date'])); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($year['end_date'])); ?></td>
                                                    <td>
                                                        <?php if ($year['is_active']): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $year['student_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning"><?php echo $year['fee_structure_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <?php if (!$year['is_active']): ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="academic_year_id" value="<?php echo $year['id']; ?>">
                                                                    <button type="submit" name="set_current_year" class="btn btn-success btn-sm" 
                                                                            onclick="return confirm('Set <?php echo $year['academic_year']; ?> as current academic year?')">
                                                                        <i class="bi bi-check-circle"></i> Set Current
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            
                                                            <a href="?action=edit&id=<?php echo $year['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            
                                                            <?php if (!$year['is_active'] && $year['student_count'] == 0): ?>
                                                                <a href="?delete=<?php echo $year['id']; ?>" class="btn btn-outline-danger btn-sm" 
                                                                   onclick="return confirmDelete('Are you sure you want to delete this academic year?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action == 'add' || $action == 'edit'): ?>
                    <!-- Add/Edit Academic Year Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <?php echo $action == 'add' ? 'Add New Academic Year' : 'Edit Academic Year'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <?php if ($action == 'edit'): ?>
                                    <input type="hidden" name="academic_year_id" value="<?php echo $academic_year_data['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="academic_year" class="form-label">Academic Year *</label>
                                        <?php if ($action == 'add'): ?>
                                            <input type="text" class="form-input" name="academic_year" id="academic_year" 
                                                   placeholder="e.g., 2024-2025" pattern="^\d{4}-\d{4}$" required
                                                   value="<?php echo htmlspecialchars($academic_year_data['academic_year'] ?? ''); ?>">
                                            <div class="form-text">Format: YYYY-YYYY (e.g., 2024-2025)</div>
                                        <?php else: ?>
                                            <input type="text" class="form-input" readonly
                                                   value="<?php echo htmlspecialchars($academic_year_data['academic_year']); ?>">
                                            <div class="form-text">Academic year cannot be changed after creation</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="is_active" class="form-label">
                                            <input type="checkbox" name="is_active" id="is_active"
                                                   <?php echo (isset($academic_year_data) && $academic_year_data['is_active']) ? 'checked' : ''; ?>>
                                            Set as Current Academic Year
                                        </label>
                                        <div class="form-text">
                                            Only one academic year can be active at a time. Setting this as current will deactivate others.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="start_date" class="form-label">Start Date *</label>
                                        <input type="date" class="form-input" name="start_date" id="start_date" required
                                               value="<?php echo $academic_year_data['start_date'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="end_date" class="form-label">End Date *</label>
                                        <input type="date" class="form-input" name="end_date" id="end_date" required
                                               value="<?php echo $academic_year_data['end_date'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="<?php echo $action == 'add' ? 'add_academic_year' : 'update_academic_year'; ?>" class="btn btn-primary">
                                        <i class="bi bi-<?php echo $action == 'add' ? 'plus' : 'pencil'; ?>"></i>
                                        <?php echo $action == 'add' ? 'Add' : 'Update'; ?> Academic Year
                                    </button>
                                    <a href="?" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Confirmation dialog
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item?');
        }
        
        // Auto-generate academic year from start date
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const academicYearInput = document.getElementById('academic_year');
            
            if (startDateInput && endDateInput && academicYearInput) {
                function updateAcademicYear() {
                    const startDate = new Date(startDateInput.value);
                    const endDate = new Date(endDateInput.value);
                    
                    if (startDate && endDate) {
                        const startYear = startDate.getFullYear();
                        const endYear = endDate.getFullYear();
                        
                        if (endYear > startYear) {
                            academicYearInput.value = startYear + '-' + endYear;
                        }
                    }
                }
                
                startDateInput.addEventListener('change', function() {
                    if (this.value && !endDateInput.value) {
                        const startDate = new Date(this.value);
                        const endDate = new Date(startDate.getFullYear() + 1, startDate.getMonth(), startDate.getDate() - 1);
                        endDateInput.value = endDate.toISOString().split('T')[0];
                    }
                    updateAcademicYear();
                });
                
                endDateInput.addEventListener('change', updateAcademicYear);
            }
        });
    </script>
</body>
</html>
