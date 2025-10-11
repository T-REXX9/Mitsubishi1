<?php
// Initialize the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
$db_path = __DIR__ . '/database/db_conn.php';
if (file_exists($db_path)) {
    include_once($db_path);
    // Use the existing $connect variable from db_conn.php
    $pdo = $connect ?? null;
} else {
    $pdo = null;
    error_log("Database connection file not found: " . $db_path);
}

// Make database connection available globally
$GLOBALS['pdo'] = $pdo;


// Define user role variable for use in all pages
$user_role = $_SESSION['user_role'] ?? null;

// Set application-wide constants and configurations
define('APP_NAME', 'Mitsubishi Dealership System');
define('APP_VERSION', '1.0.0');

// Default timezone setting
date_default_timezone_set('Asia/Manila'); // Set appropriate timezone for Philippines

// Error reporting settings (turn off in production)
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user has specified role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to redirect with a message
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $location");
    exit();
}