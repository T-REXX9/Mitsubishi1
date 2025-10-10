<?php
session_start();

// Set up test session if not already set
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'Customer';
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_customer';
}

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'user_role' => $_SESSION['user_role'] ?? 'Not set',
    'user_id' => $_SESSION['user_id'] ?? 'Not set',
    'username' => $_SESSION['username'] ?? 'Not set',
    'session_data' => $_SESSION
]);
?>