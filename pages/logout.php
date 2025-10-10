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

session_destroy();
header("Location: login.php");
exit;
?>
