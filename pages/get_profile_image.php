<?php
session_start();
include_once(dirname(__DIR__) . '/database/db_conn.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

// Get user ID from parameter or session
$userId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

// Security check - users can only access their own profile image unless they're admin
if ($userId !== $_SESSION['user_id'] && $_SESSION['user_role'] !== 'Admin') {
    http_response_code(403);
    exit('Access denied');
}

try {
    // Fetch profile image from database
    $stmt = $connect->prepare("SELECT ProfileImage FROM accounts WHERE Id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['ProfileImage']) {
        // Set appropriate headers
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . strlen($result['ProfileImage']));
        header('Cache-Control: private, max-age=86400'); // Cache for 24 hours
        
        // Output the image
        echo $result['ProfileImage'];
    } else {
        // Return a default avatar or 404
        http_response_code(404);
        exit('Image not found');
    }
} catch (Exception $e) {
    http_response_code(500);
    exit('Server error');
}
?>
