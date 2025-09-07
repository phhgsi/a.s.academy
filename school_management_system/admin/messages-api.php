<?php
require_once '../config/database.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'unread_count':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            $count = $stmt->fetchColumn();
            echo json_encode(['count' => (int)$count]);
            break;
            
        case 'recent_messages':
            $stmt = $pdo->prepare("
                SELECT m.*, u.full_name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE m.receiver_id = ? AND m.is_read = 0 
                ORDER BY m.created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $messages = $stmt->fetchAll();
            echo json_encode(['messages' => $messages]);
            break;
            
        case 'mark_read':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $message_id = $_POST['message_id'] ?? null;
                if ($message_id) {
                    $stmt = $pdo->prepare("
                        UPDATE messages 
                        SET is_read = 1, read_at = NOW() 
                        WHERE id = ? AND receiver_id = ?
                    ");
                    $stmt->execute([$message_id, $user_id]);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['error' => 'Message ID required']);
                }
            } else {
                echo json_encode(['error' => 'POST method required']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
