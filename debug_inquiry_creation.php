<?php
session_start();
require_once 'includes/database/db_conn.php';

// Use the global $connect variable from db_conn.php
global $connect;
$pdo = $connect;

// Check recent inquiries and their CreatedBy values
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.Id,
            i.FullName,
            i.Email,
            i.AccountId,
            i.CreatedBy,
            i.InquiryDate,
            a.username as CreatedByUsername,
            a.role as CreatedByRole
        FROM inquiries i
        LEFT JOIN accounts a ON i.CreatedBy = a.Id
        ORDER BY i.InquiryDate DESC
        LIMIT 10
    ");
    $stmt->execute();
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Recent Inquiries Debug</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Account ID</th>
            <th>Created By</th>
            <th>Created By Username</th>
            <th>Created By Role</th>
            <th>Inquiry Date</th>
          </tr>";
    
    foreach ($inquiries as $inquiry) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($inquiry['Id']) . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['FullName']) . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['Email']) . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['AccountId']) . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['CreatedBy'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['CreatedByUsername'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['CreatedByRole'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($inquiry['InquiryDate']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check current session info
    echo "<h3>Current Session Info</h3>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
    echo "<p>Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>