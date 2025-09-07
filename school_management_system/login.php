<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role, full_name, is_active FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'teacher':
                    header('Location: teacher/dashboard.php');
                    break;
                case 'cashier':
                    header('Location: cashier/dashboard.php');
                    break;
                case 'student':
                    header('Location: student/dashboard.php');
                    break;
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Get school information for branding
$stmt = $pdo->prepare("SELECT school_name, logo FROM school_info WHERE id = 1");
$stmt->execute();
$school_info = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $school_info ? $school_info['school_name'] : 'School Management System'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <?php if ($school_info && $school_info['logo']): ?>
                    <img src="uploads/<?php echo $school_info['logo']; ?>" alt="School Logo" style="max-width: 80px; margin-bottom: 1rem;">
                <?php endif; ?>
                <h1><?php echo $school_info ? $school_info['school_name'] : 'School Management'; ?></h1>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">Management System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required 
                           placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required 
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary login-btn">
                        🔐 Login
                    </button>
                </div>
                
                <div class="text-center">
                    <small style="color: var(--text-secondary);">
                        Forgot your password? Contact the administrator.
                    </small>
                </div>
            </form>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <div class="text-center">
                    <small style="color: var(--text-secondary);">
                        <strong>Demo Credentials:</strong><br>
                        Admin: admin / admin123<br>
                        Teacher: teacher / teacher123<br>
                        Cashier: cashier / cashier123<br>
                        Student: student / student123
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Form validation
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    </script>
</body>
</html>
