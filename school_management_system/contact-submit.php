<?php
session_start();
require_once 'config/database.php';

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Validate and sanitize inputs
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Name is required.';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($subject)) {
            $errors[] = 'Subject is required.';
        }
        
        if (empty($message)) {
            $errors[] = 'Message is required.';
        }
        
        if (!empty($errors)) {
            $response['message'] = implode(' ', $errors);
        } else {
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO contact_inquiries (name, email, phone, subject, message, status) 
                VALUES (?, ?, ?, ?, ?, 'new')
            ");
            
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Thank you for your message! We will get back to you soon.';
                
                // Optional: Send email notification to admin
                // You can implement email functionality here if needed
                
            } else {
                $response['message'] = 'Failed to submit your message. Please try again.';
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'An error occurred while processing your request. Please try again.';
        error_log('Contact form error: ' . $e->getMessage());
    }
    
    // Handle AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Handle regular form submission
    $_SESSION['contact_response'] = $response;
    header('Location: contact.php');
    exit;
}

// Redirect if accessed directly
header('Location: index.php');
exit;
?>
