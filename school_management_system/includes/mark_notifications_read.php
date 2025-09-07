<?php
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    if (isset($_POST['mark_all']) && $_POST['mark_all'] == '1') {
        // Mark all notifications as read for this user
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE (user_id = ? OR user_id IS NULL) 
            AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    } elseif (isset($_POST['notification_id'])) {
        // Mark specific notification as read
        $notification_id = $_POST['notification_id'];
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND (user_id = ? OR user_id IS NULL)
        ");
        $stmt->execute([$notification_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No notification specified'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error updating notifications: ' . $e->getMessage()
    ]);
}
?>
