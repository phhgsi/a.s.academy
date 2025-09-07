<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Get subjects with class and teacher information
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.class_name, c.section, u.full_name as teacher_name 
        FROM subjects s 
        LEFT JOIN classes c ON s.class_id = c.id 
        LEFT JOIN users u ON s.teacher_id = u.id 
        ORDER BY c.class_name, c.section, s.subject_name
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {
    $subjects = [];
    $error_message = 'Error fetching subjects: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - School Management System</title>
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
                    <h1 class="page-title">Subjects Management</h1>
                    <p class="page-subtitle">Manage subject information and teacher assignments</p>
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
                        <h3 class="card-title">Subjects List</h3>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add New Subject
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Subject Code</th>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No subjects found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['subject_code'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php 
                                                if ($subject['class_name']) {
                                                    echo htmlspecialchars($subject['class_name']);
                                                    if ($subject['section']) {
                                                        echo ' - ' . htmlspecialchars($subject['section']);
                                                    }
                                                } else {
                                                    echo 'Not Assigned';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not Assigned'); ?></td>
                                            <td>
                                                <?php if ($subject['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
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
