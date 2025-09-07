<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Error - A.S.ACADEMY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 2rem;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            margin: 2rem;
            animation: errorPageFadeIn 0.6s ease-out;
        }
        
        @keyframes errorPageFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .error-icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
            animation: errorPulse 2s ease-in-out infinite;
        }
        
        @keyframes errorPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #1e293b, #475569);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .error-btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .error-btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .error-btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        .error-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .error-details {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            border-left: 4px solid #3b82f6;
        }
        
        .error-details summary {
            cursor: pointer;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .error-details-content {
            font-size: 0.8rem;
            color: #6b7280;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        .error-help {
            margin-top: 2rem;
            padding: 1rem;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 0.75rem;
            color: #92400e;
        }
        
        .error-help h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-help ul {
            margin: 0;
            padding-left: 1.5rem;
            font-size: 0.9rem;
        }
        
        .error-help li {
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-message {
                font-size: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .error-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        
        <h1 class="error-title">Oops! Something went wrong</h1>
        
        <p class="error-message">
            <?php echo isset($message) ? htmlspecialchars($message) : 'We encountered an unexpected error while processing your request. Our team has been notified and will resolve this issue as soon as possible.'; ?>
        </p>
        
        <div class="error-actions">
            <a href="javascript:history.back()" class="error-btn error-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                Go Back
            </a>
            <a href="<?php echo getBaseUrl(); ?>/admin/dashboard.php" class="error-btn error-btn-primary">
                <i class="bi bi-house-fill"></i>
                Go to Dashboard
            </a>
        </div>
        
        <?php if (isset($_GET['debug']) && ($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <details class="error-details">
                <summary>
                    <i class="bi bi-code-slash"></i>
                    Technical Details (Admin Only)
                </summary>
                <div class="error-details-content">
Error Time: <?php echo date('Y-m-d H:i:s'); ?>
URL: <?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?>
User Agent: <?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'; ?>
IP Address: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?>
User ID: <?php echo $_SESSION['user_id'] ?? 'Guest'; ?>
Session ID: <?php echo session_id(); ?>

<?php if (isset($context)): ?>
Additional Context:
<?php echo json_encode($context, JSON_PRETTY_PRINT); ?>
<?php endif; ?>
                </div>
            </details>
        <?php endif; ?>
        
        <div class="error-help">
            <h4>
                <i class="bi bi-lightbulb-fill"></i>
                What can you do?
            </h4>
            <ul>
                <li>Try refreshing the page in a few moments</li>
                <li>Check your internet connection</li>
                <li>Go back to the previous page and try again</li>
                <li>Contact support if the problem persists</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-refresh after 30 seconds
        setTimeout(function() {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'error-btn error-btn-primary';
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Page';
            refreshBtn.onclick = () => window.location.reload();
            
            const actions = document.querySelector('.error-actions');
            actions.appendChild(refreshBtn);
        }, 30000);
        
        // Track error page views
        if (typeof gtag !== 'undefined') {
            gtag('event', 'exception', {
                'description': '<?php echo $message ?? "Unknown error"; ?>',
                'fatal': false
            });
        }
    </script>
</body>
</html>
