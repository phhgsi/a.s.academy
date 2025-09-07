<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['imageData']) || !isset($input['filename'])) {
        http_response_code(400);
        exit(json_encode(['error' => 'Missing image data or filename']));
    }
    
    $imageData = $input['imageData'];
    $filename = $input['filename'];
    
    // Remove data URL prefix
    if (strpos($imageData, 'data:image/') === 0) {
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
    }
    
    // Decode base64
    $imageData = base64_decode($imageData);
    
    if ($imageData === false) {
        http_response_code(400);
        exit(json_encode(['error' => 'Invalid image data']));
    }
    
    // Create uploads directory if it doesn't exist
    $target_dir = "../uploads/photos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = 'jpg';
    $unique_filename = 'camera_' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;
    
    // Save the image
    if (file_put_contents($target_file, $imageData)) {
        echo json_encode([
            'success' => true,
            'filename' => $unique_filename,
            'path' => $target_file
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save image']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
