<?php
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle profile update
if ($_POST['action'] ?? '' === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) {
        $error_message = 'Full name is required.';
    } elseif (empty($email)) {
        $error_message = 'Email is required.';
    } else {
        // If password change is requested
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $error_message = 'Current password is required to change password.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error_message = 'Current password is incorrect.';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'New passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'New password must be at least 6 characters long.';
            } else {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$full_name, $email, $hashed_password, $user_id]);
                $success_message = 'Profile and password updated successfully.';
            }
        } else {
            // Update without password change
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            $success_message = 'Profile updated successfully.';
        }
        
        if ($success_message) {
            $_SESSION['user_name'] = $full_name;
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>My Profile - School Management System</title>
    <link rel=\"stylesheet\" href=\"../assets/css/style.css\">
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css\">
</head>
<body>
    <div class=\"wrapper\">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class=\"main-content\">
            <?php include '../includes/header.php'; ?>
            
            <div class=\"content-wrapper\">
                <div class=\"page-header\">
                    <h1 class=\"page-title\">My Profile</h1>
                    <p class=\"page-subtitle\">Manage your account settings and profile information</p>
                </div>

                <?php if ($success_message): ?>
                    <div class=\"alert alert-success mb-4\">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class=\"alert alert-danger mb-4\">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class=\"row\">
                    <div class=\"col-md-8\">
                        <div class=\"card\">
                            <div class=\"card-header\">
                                <h3 class=\"card-title\">Profile Information</h3>
                            </div>
                            <form method=\"POST\" action=\"\">
                                <input type=\"hidden\" name=\"action\" value=\"update_profile\">
                                <div class=\"card-body\">
                                    <div class=\"mb-3\">
                                        <label for=\"username\" class=\"form-label\">Username</label>
                                        <input type=\"text\" class=\"form-control\" id=\"username\" value=\"<?php echo htmlspecialchars($user['username']); ?>\" readonly>
                                        <small class=\"form-text text-muted\">Username cannot be changed.</small>
                                    </div>

                                    <div class=\"mb-3\">
                                        <label for=\"full_name\" class=\"form-label\">Full Name *</label>
                                        <input type=\"text\" class=\"form-control\" id=\"full_name\" name=\"full_name\" value=\"<?php echo htmlspecialchars($user['full_name']); ?>\" required>
                                    </div>

                                    <div class=\"mb-3\">
                                        <label for=\"email\" class=\"form-label\">Email *</label>
                                        <input type=\"email\" class=\"form-control\" id=\"email\" name=\"email\" value=\"<?php echo htmlspecialchars($user['email']); ?>\" required>
                                    </div>

                                    <div class=\"mb-3\">
                                        <label for=\"role\" class=\"form-label\">Role</label>
                                        <input type=\"text\" class=\"form-control\" id=\"role\" value=\"<?php echo ucfirst($user['role']); ?>\" readonly>
                                    </div>

                                    <hr class=\"my-4\">

                                    <h5 class=\"mb-3\">Change Password (Optional)</h5>
                                    
                                    <div class=\"mb-3\">
                                        <label for=\"current_password\" class=\"form-label\">Current Password</label>
                                        <input type=\"password\" class=\"form-control\" id=\"current_password\" name=\"current_password\">
                                        <small class=\"form-text text-muted\">Required only if changing password.</small>
                                    </div>

                                    <div class=\"mb-3\">
                                        <label for=\"new_password\" class=\"form-label\">New Password</label>
                                        <input type=\"password\" class=\"form-control\" id=\"new_password\" name=\"new_password\">
                                    </div>

                                    <div class=\"mb-3\">
                                        <label for=\"confirm_password\" class=\"form-label\">Confirm New Password</label>
                                        <input type=\"password\" class=\"form-control\" id=\"confirm_password\" name=\"confirm_password\">
                                    </div>
                                </div>

                                <div class=\"card-footer\">
                                    <button type=\"submit\" class=\"btn btn-primary\">
                                        <i class=\"bi bi-check-circle me-2\"></i>Update Profile
                                    </button>
                                    <a href=\"dashboard.php\" class=\"btn btn-secondary\">
                                        <i class=\"bi bi-arrow-left me-2\"></i>Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class=\"col-md-4\">
                        <div class=\"card\">
                            <div class=\"card-header\">
                                <h3 class=\"card-title\">Account Information</h3>
                            </div>
                            <div class=\"card-body\">
                                <div class=\"text-center mb-3\">
                                    <div class=\"avatar-circle\" style=\"width: 80px; height: 80px; font-size: 2rem; margin: 0 auto;\">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                </div>

                                <table class=\"table table-borderless\">
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td><span class=\"badge bg-primary\"><?php echo ucfirst($user['role']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class=\"badge bg-success\">Active</span>
                                            <?php else: ?>
                                                <span class=\"badge bg-danger\">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
