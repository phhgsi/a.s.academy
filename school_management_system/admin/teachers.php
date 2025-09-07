<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        // Add teacher logic here
        $success_message = 'Teacher added successfully.';
    } elseif ($_POST['action'] === 'edit') {
        // Edit teacher logic here
        $success_message = 'Teacher updated successfully.';
    }
}

// Get teachers list
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email, u.is_active 
    FROM teachers t 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.first_name, t.last_name
");
$stmt->execute();
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">Teachers Management</h1>
                    <p class="page-subtitle">Manage teacher information and assignments</p>
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

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Teachers List</h3>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add New Teacher
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Qualification</th>
                                    <th>Department</th>
                                    <th>Mobile</th>
                                    <th>Experience</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($teachers)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No teachers found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['employee_id']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                                <?php if ($teacher['email']): ?>
                                                    <small class="d-block text-muted"><?php echo htmlspecialchars($teacher['email']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($teacher['qualification'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['department'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['mobile_no'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['experience_years']) . ' years'; ?></td>
                                            <td>
                                                <?php if ($teacher['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>Edit
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
