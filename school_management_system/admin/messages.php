<?php
require_once '../config/database.php';
require_once '../includes/academic_year.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$current_academic_year = getCurrentAcademicYear();

// Handle message actions
$action = $_GET['action'] ?? 'inbox';
$message_id = $_GET['id'] ?? null;

// Handle POST requests for sending messages and marking as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $receiver_id = $_POST['receiver_id'];
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $priority = $_POST['priority'] ?? 'normal';
        
        if (!empty($receiver_id) && !empty($subject) && !empty($message)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (sender_id, receiver_id, subject, message, priority) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $receiver_id, $subject, $message, $priority]);
                $success_message = "Message sent successfully!";
            } catch (Exception $e) {
                $error_message = "Failed to send message: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }
    
    if (isset($_POST['mark_read'])) {
        $msg_id = $_POST['message_id'];
        try {
            $stmt = $pdo->prepare("
                UPDATE messages 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND receiver_id = ?
            ");
            $stmt->execute([$msg_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error marking message as read: " . $e->getMessage());
        }
    }
}

// Get inbox messages
$inbox_messages = [];
$sent_messages = [];
$unread_count = 0;

try {
    // Inbox messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name as sender_name, u.role as sender_role
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.receiver_id = ? 
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $inbox_messages = $stmt->fetchAll();
    
    // Sent messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name as receiver_name, u.role as receiver_role
        FROM messages m 
        JOIN users u ON m.receiver_id = u.id 
        WHERE m.sender_id = ? 
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $sent_messages = $stmt->fetchAll();
    
    // Unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Error fetching messages: " . $e->getMessage());
}

// Get users for compose dropdown
try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, role 
        FROM users 
        WHERE id != ? AND is_active = 1 
        ORDER BY role, full_name
    ");
    $stmt->execute([$user_id]);
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            height: calc(100vh - 140px);
        }
        
        .messages-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            overflow-y: auto;
        }
        
        .messages-main {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            overflow-y: auto;
        }
        
        .message-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            transition: all 0.3s ease;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .tab-btn:hover:not(.active) {
            background: var(--light-color);
        }
        
        .message-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .message-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .message-item:hover {
            background: var(--light-color);
        }
        
        .message-item.unread {
            background: rgba(59, 130, 246, 0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .message-sender {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .message-time {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .message-subject {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .message-preview {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        .compose-form {
            background: var(--light-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        
        .compose-form.hidden {
            display: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .priority-high { background: #fee2e2; color: #dc2626; }
        .priority-normal { background: #f3f4f6; color: #6b7280; }
        .priority-low { background: #dbeafe; color: #2563eb; }
        
        .message-detail {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 1rem;
        }
        
        .detail-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-subject {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .detail-meta {
            display: flex;
            gap: 2rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .detail-content {
            line-height: 1.6;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="bi bi-chat-dots"></i>
                        Messages
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </h1>
                    <p class="page-subtitle">Internal communication system</p>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Compose Message Form -->
                <div class="compose-form <?php echo $action !== 'compose' ? 'hidden' : ''; ?>" id="composeForm">
                    <h3><i class="bi bi-pencil-square"></i> Compose Message</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">To:</label>
                                <select name="receiver_id" class="form-control" required>
                                    <option value="">Select recipient</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo ucfirst($user['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Priority:</label>
                                <select name="priority" class="form-control">
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Subject:</label>
                            <input type="text" name="subject" class="form-control" placeholder="Enter message subject" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Message:</label>
                            <textarea name="message" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleCompose()">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="messages-container">
                    <!-- Messages Sidebar -->
                    <div class="messages-sidebar">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Messages</h4>
                            <button class="btn btn-primary btn-sm" onclick="toggleCompose()">
                                <i class="bi bi-plus"></i> New
                            </button>
                        </div>
                        
                        <div class="message-tabs">
                            <button class="tab-btn <?php echo $action === 'inbox' ? 'active' : ''; ?>" onclick="showTab('inbox')">
                                Inbox (<?php echo $unread_count; ?>)
                            </button>
                            <button class="tab-btn <?php echo $action === 'sent' ? 'active' : ''; ?>" onclick="showTab('sent')">
                                Sent
                            </button>
                        </div>
                        
                        <!-- Inbox Messages -->
                        <div id="inbox-tab" class="message-list <?php echo $action !== 'inbox' ? 'hidden' : ''; ?>">
                            <?php if (!empty($inbox_messages)): ?>
                                <?php foreach ($inbox_messages as $msg): ?>
                                    <div class="message-item <?php echo !$msg['is_read'] ? 'unread' : ''; ?>" 
                                         onclick="viewMessage(<?php echo $msg['id']; ?>, 'inbox')">
                                        <div class="message-header">
                                            <span class="message-sender"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                            <span class="message-time"><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                            <?php if ($msg['priority'] !== 'normal'): ?>
                                                <span class="priority-badge priority-<?php echo $msg['priority']; ?>">
                                                    <?php echo ucfirst($msg['priority']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>...
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-secondary);"></i>
                                    <p style="color: var(--text-secondary); margin-top: 1rem;">No messages in inbox</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Sent Messages -->
                        <div id="sent-tab" class="message-list <?php echo $action !== 'sent' ? 'hidden' : ''; ?>">
                            <?php if (!empty($sent_messages)): ?>
                                <?php foreach ($sent_messages as $msg): ?>
                                    <div class="message-item" onclick="viewMessage(<?php echo $msg['id']; ?>, 'sent')">
                                        <div class="message-header">
                                            <span class="message-sender">To: <?php echo htmlspecialchars($msg['receiver_name']); ?></span>
                                            <span class="message-time"><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                            <?php if ($msg['priority'] !== 'normal'): ?>
                                                <span class="priority-badge priority-<?php echo $msg['priority']; ?>">
                                                    <?php echo ucfirst($msg['priority']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>...
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-send" style="font-size: 3rem; color: var(--text-secondary);"></i>
                                    <p style="color: var(--text-secondary); margin-top: 1rem;">No sent messages</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Message Detail View -->
                    <div class="messages-main">
                        <div id="message-detail-area">
                            <div class="text-center py-5">
                                <i class="bi bi-chat-dots" style="font-size: 4rem; color: var(--text-secondary);"></i>
                                <h4 style="color: var(--text-secondary); margin-top: 1rem;">Select a message to view</h4>
                                <p style="color: var(--text-secondary);">Choose a message from the list to read its contents</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        let currentMessages = {
            inbox: <?php echo json_encode($inbox_messages); ?>,
            sent: <?php echo json_encode($sent_messages); ?>
        };
        
        function showTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide message lists
            document.getElementById('inbox-tab').classList.toggle('hidden', tab !== 'inbox');
            document.getElementById('sent-tab').classList.toggle('hidden', tab !== 'sent');
            
            // Clear message detail
            document.getElementById('message-detail-area').innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots" style="font-size: 4rem; color: var(--text-secondary);"></i>
                    <h4 style="color: var(--text-secondary); margin-top: 1rem;">Select a message to view</h4>
                    <p style="color: var(--text-secondary);">Choose a message from the list to read its contents</p>
                </div>
            `;
        }
        
        function toggleCompose() {
            const form = document.getElementById('composeForm');
            form.classList.toggle('hidden');
        }
        
        function viewMessage(messageId, type) {
            const messages = currentMessages[type];
            const message = messages.find(m => m.id == messageId);
            
            if (message) {
                const detailArea = document.getElementById('message-detail-area');
                const isInbox = type === 'inbox';
                const contactName = isInbox ? message.sender_name : message.receiver_name;
                const contactRole = isInbox ? message.sender_role : message.receiver_role;
                
                detailArea.innerHTML = `
                    <div class="message-detail">
                        <div class="detail-header">
                            <div class="detail-subject">${message.subject}</div>
                            <div class="detail-meta">
                                <span><i class="bi bi-person"></i> ${isInbox ? 'From' : 'To'}: ${contactName} (${contactRole})</span>
                                <span><i class="bi bi-clock"></i> ${new Date(message.created_at).toLocaleString()}</span>
                                ${message.priority !== 'normal' ? `<span class="priority-badge priority-${message.priority}">${message.priority.toUpperCase()}</span>` : ''}
                            </div>
                        </div>
                        <div class="detail-content">
                            ${message.message.replace(/\n/g, '<br>')}
                        </div>
                        ${isInbox && !message.is_read ? `
                            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="message_id" value="${message.id}">
                                    <button type="submit" name="mark_read" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-check2"></i> Mark as Read
                                    </button>
                                </form>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                // Mark as read automatically if viewing in inbox
                if (isInbox && !message.is_read) {
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `mark_read=1&message_id=${messageId}`
                    });
                }
            }
        }
        
        // Auto-refresh unread count every 30 seconds
        setInterval(() => {
            fetch('messages-api.php?action=unread_count')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        badge.textContent = data.count;
                        if (data.count === 0) {
                            badge.style.display = 'none';
                        } else {
                            badge.style.display = 'inline';
                        }
                    }
                })
                .catch(error => console.log('Error checking messages:', error));
        }, 30000);
    </script>
</body>
</html>
