<?php
session_start();

// Include database connection
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// If user is a Sales Agent, update their status to Inactive
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'SalesAgent') {
    try {
        $updateStatus = $connect->prepare("UPDATE sales_agent_profiles SET status = 'Inactive' WHERE account_id = ?");
        $updateStatus->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Error updating agent status on logout: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
?>
