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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        try {
            $username = trim($_POST['username']);
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = $_POST['password'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists');
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, full_name, email, password, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$username, $full_name, $email, $hashed_password, $role, $is_active]);
            
            $success_message = 'User added successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error_message = 'Error adding user: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_user'])) {
        try {
            $user_id = $_POST['user_id'];
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    role = ?, 
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $role, $is_active, $user_id]);
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
            }
            
            $success_message = 'User updated successfully!';
            $action = 'list';
        } catch (Exception $e) {
            $error_message = 'Error updating user: ' . $e->getMessage();
        }
    }
}

// Get single user for editing
if ($action == 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user_data = $stmt->fetch();
        
        if (!$user_data) {
            $action = 'list';
            $error_message = 'User not found!';
        }
    } catch (Exception $e) {
        $action = 'list';
        $error_message = 'Error loading user: ' . $e->getMessage();
    }
}

// Get all users
try {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY role, full_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
    $error_message = 'Error fetching users: ' . $e->getMessage();
}

// Get user statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            role,
            COUNT(*) as count,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
        FROM users 
        GROUP BY role
    ");
    $stmt->execute();
    $user_stats = [];
    while ($row = $stmt->fetch()) {
        $user_stats[$row['role']] = $row;
    }
} catch (Exception $e) {
    $user_stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - School Management System</title>
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
                    <h1 class="page-title">Users Management</h1>
                    <p class="page-subtitle">Manage system users and their roles</p>
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

                <?php if ($action == 'list'): ?>
                    <!-- User Statistics -->
                    <div class="stats-grid">
                        <?php 
                        $role_icons = [
                            'admin' => '👑',
                            'teacher' => '👨‍🏫',
                            'cashier' => '💰',
                            'student' => '👨‍🎓'
                        ];
                        ?>
                        <?php foreach (['admin', 'teacher', 'cashier', 'student'] as $role): ?>
                            <?php $stats = $user_stats[$role] ?? ['count' => 0, 'active_count' => 0]; ?>
                            <div class="stat-card">
                                <div class="stat-content">
                                    <h3><?php echo $stats['count']; ?></h3>
                                    <p><?php echo ucfirst($role) . 's'; ?> (<?php echo $stats['active_count']; ?> active)</p>
                                </div>
                                <div class="stat-icon">
                                    <?php echo $role_icons[$role]; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Users</h3>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i>Add New User
                            </a>
                        </div>
                        
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge badge-primary"><?php echo ucfirst($user['role']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
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
                    
                <?php elseif ($action == 'add' || $action == 'edit'): ?>
                    <!-- Add/Edit User Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <?php echo $action == 'add' ? 'Add New User' : 'Edit User'; ?>
                            </h3>
                            <a href="?" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if ($action == 'edit'): ?>
                                    <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-grid">
                                    <?php if ($action == 'add'): ?>
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" class="form-input" name="username" id="username" 
                                               placeholder="Enter username" required
                                               value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>">
                                    </div>
                                    <?php else: ?>
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-input" readonly
                                               value="<?php echo htmlspecialchars($user_data['username']); ?>">
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-input" name="full_name" id="full_name" 
                                               placeholder="Enter full name" required
                                               value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-input" name="email" id="email" 
                                               placeholder="Enter email address"
                                               value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="role" class="form-label">Role *</label>
                                        <select class="form-select" name="role" id="role" required>
                                            <option value="">Select Role</option>
                                            <option value="admin" <?php echo (isset($user_data) && $user_data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="teacher" <?php echo (isset($user_data) && $user_data['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                            <option value="cashier" <?php echo (isset($user_data) && $user_data['role'] == 'cashier') ? 'selected' : ''; ?>>Cashier</option>
                                            <option value="student" <?php echo (isset($user_data) && $user_data['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password" class="form-label">
                                            Password <?php echo $action == 'add' ? '*' : '(leave blank to keep current)'; ?>
                                        </label>
                                        <input type="password" class="form-input" name="password" id="password" 
                                               placeholder="Enter password" <?php echo $action == 'add' ? 'required' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="is_active" class="form-label">
                                            <input type="checkbox" name="is_active" id="is_active" 
                                                   <?php echo (isset($user_data) && $user_data['is_active']) || !isset($user_data) ? 'checked' : ''; ?>>
                                            Active User
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" name="<?php echo $action == 'add' ? 'add_user' : 'update_user'; ?>" class="btn btn-primary">
                                        <i class="bi bi-<?php echo $action == 'add' ? 'plus' : 'save'; ?>"></i>
                                        <?php echo $action == 'add' ? 'Add' : 'Update'; ?> User
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

    <script src="../assets/js/main.js"></script>
</body>
</html>
