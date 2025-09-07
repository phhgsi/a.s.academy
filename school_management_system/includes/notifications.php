<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? 'get';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'mark_read') {
        $notification_id = $_POST['id'] ?? '';
        
        if (empty($notification_id)) {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            exit;
        }
        
        // Mark notification as read (only if it belongs to current user)
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$notification_id, $user_id]);
        
        echo json_encode(['success' => $result]);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        // Mark all user's notifications as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $result = $stmt->execute([$user_id]);
        
        echo json_encode(['success' => $result]);
        exit;
    }
}

try {
    // Handle count_only parameter
    if (isset($_GET['count_only'])) {
        // Get only unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE");
        $stmt->execute([$user_id]);
        $unread_count = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'unread_count' => (int)$unread_count]);
        exit();
    }
    
    // Get notifications for current user
    $limit = $_GET['limit'] ?? 10;
    $offset = $_GET['offset'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) 
        ORDER BY created_at DESC, is_read ASC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $limit, $offset]);
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetch()['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
} catch (Exception $e) {
    // If notifications table doesn't exist, return empty response
    echo json_encode([
        'success' => true,
        'notifications' => [],
        'unread_count' => 0
    ]);
}

// Helper function to create notifications (can be called from other files)
function createNotification($user_id, $type, $title, $message) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $type, $title, $message]);
}

// Helper function to create bulk notifications for multiple users
function createBulkNotifications($user_ids, $type, $title, $message) {
    global $pdo;
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($user_ids as $user_id) {
            $stmt->execute([$user_id, $type, $title, $message]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// Helper function to create notifications for all users of a specific role
function createRoleNotifications($role, $type, $title, $message) {
    global $pdo;
    
    // Get all users with the specified role
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND is_active = 1");
    $stmt->execute([$role]);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($users)) {
        return createBulkNotifications($users, $type, $title, $message);
    }
    
    return false;
}
?>
