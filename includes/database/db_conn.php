<?php
// Set error handling before anything else
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// db_connection.php

// Include the EnvLoader to load environment variables
$env_loader_path = __DIR__ . '/../utils/EnvLoader.php';
if (file_exists($env_loader_path)) {
    require_once $env_loader_path;
    \Mitsubishi\Utils\EnvLoader::load();
}

// Include the host helper
$host_helper_path = __DIR__ . '/../host/host_helper.php';
if (file_exists($host_helper_path)) {
    include_once $host_helper_path;
}

try {
    global $connect;

    // Load database credentials from environment variables
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_name = getenv('DB_NAME') ?: 'mitsubishi';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_password = getenv('DB_PASSWORD') ?: '';

    // Ensure connection uses UTF-8 to prevent json_encode failures on non-ASCII data
    $connect = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_password);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connect->setAttribute(PDO::ATTR_TIMEOUT, 300); // 5 minutes timeout
    $connect->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8mb4");
    
    // Set MySQL session variables for large data handling
    $connect->exec("SET SESSION wait_timeout = 300");
    $connect->exec("SET SESSION interactive_timeout = 300");
    // Note: max_allowed_packet is read-only at session level, must be set globally in MySQL config
    $connect->exec("SET SESSION net_read_timeout = 60");
    $connect->exec("SET SESSION net_write_timeout = 60");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    // For AJAX-style requests (POST or GET) that specify an action, return JSON error instead of HTML
    $hasActionParam = isset($_POST['action']) || isset($_GET['action']);
    if ($hasActionParam && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    // Fallback for non-AJAX contexts
    die("Database connection failed: " . $e->getMessage());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set a global variable for the base URL
$GLOBALS['base_url'] = function_exists('getBaseUrl') ? getBaseUrl() : '';
