<?php
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$count_only = isset($_GET['count_only']) && $_GET['count_only'];

try {
    if ($count_only) {
        // Just return unread count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE (user_id = ? OR user_id IS NULL) 
            AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'unread_count' => (int)$result['unread_count']
        ]);
    } else {
        // Get notifications with details
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE (user_id = ? OR user_id IS NULL) 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();
        
        // Get unread count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE (user_id = ? OR user_id IS NULL) 
            AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        $unread_result = $stmt->fetch();
        
        // Format notifications
        $formatted_notifications = [];
        foreach ($notifications as $notification) {
            $formatted_notifications[] = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'is_read' => (bool)$notification['is_read'],
                'created_at' => date('M j, Y g:i A', strtotime($notification['created_at']))
            ];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $formatted_notifications,
            'unread_count' => (int)$unread_result['unread_count']
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error loading notifications',
        'unread_count' => 0
    ]);
}
?>
