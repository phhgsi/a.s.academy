<?php
// Password Hash Generator
// Security: Disable error reporting in production
// error_reporting(0);

// Initialize variables
$password = '';
$hashed_password = '';
$algorithm = 'PASSWORD_DEFAULT';
$options = [];
$error = '';
$success = '';

// Common password algorithms
$algorithms = [
    'PASSWORD_DEFAULT' => 'Default (bcrypt) - Recommended',
    'PASSWORD_BCRYPT' => 'BCRYPT',
    'PASSWORD_ARGON2I' => 'ARGON2I (PHP 7.2+)',
    'PASSWORD_ARGON2ID' => 'ARGON2ID (PHP 7.3+)'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $algorithm = $_POST['algorithm'] ?? 'PASSWORD_DEFAULT';
    
    // Validate password
    if (empty($password)) {
        $error = 'Please enter a password';
    } elseif (strlen($password) < 8) {
        $error = 'Password should be at least 8 characters long';
    } else {
        try {
            // Set options based on algorithm
            switch ($algorithm) {
                case 'PASSWORD_BCRYPT':
                    $options = ['cost' => 12];
                    break;
                case 'PASSWORD_ARGON2I':
                    $options = [
                        'memory_cost' => 65536,
                        'time_cost' => 4,
                        'threads' => 1
                    ];
                    break;
                case 'PASSWORD_ARGON2ID':
                    $options = [
                        'memory_cost' => 65536,
                        'time_cost' => 4,
                        'threads' => 1
                    ];
                    break;
                default:
                    $options = [];
            }
            
            // Generate hash
            $hashed_password = password_hash($password, constant($algorithm), $options);
            $success = 'Password hashed successfully!';
            
        } catch (Exception $e) {
            $error = 'Error generating hash: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        button {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #2980b9;
        }
        
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .hashed-password {
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .alert-error {
            background: #ffe6e6;
            color: #c0392b;
            border: 1px solid #c0392b;
        }
        
        .alert-success {
            background: #e6ffe6;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        .info {
            background: #d6eaf8;
            color: #2874a6;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password" value="<?php echo htmlspecialchars($password); ?>">
            </div>
            
            <div class="form-group">
                <label for="algorithm">Hashing Algorithm:</label>
                <select id="algorithm" name="algorithm">
                    <?php foreach ($algorithms as $value => $label): ?>
                        <option value="<?php echo $value; ?>" 
                            <?php echo ($algorithm === $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php if ($hashed_password): ?>
        <div class="result">
            <h3>Generated Hash:</h3>
            <div class="hashed-password"><?php echo htmlspecialchars($hashed_password); ?></div>
            
            <div style="margin-top: 15px;">
                <strong>Algorithm Info:</strong><br>
                <?php
                $info = password_get_info($hashed_password);
                echo "Algorithm: " . $info['algoName'] . "<br>";
                echo "Options: " . json_encode($info['options']);
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>💡 Security Tips:</strong>
            <ul>
                <li>Always use strong, unique passwords</li>
                <li>PASSWORD_DEFAULT is recommended as it uses the current best algorithm</li>
                <li>Never store plain text passwords</li>
                <li>Use password_verify() to check passwords against hashes</li>
                <li>Consider using password_needs_rehash() to update old hashes</li>
            </ul>
        </div>
    </div>
</body>
</html>