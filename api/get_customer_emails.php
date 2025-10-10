<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include the session initialization file
include_once(dirname(__DIR__) . '/includes/init.php');

try {
    // Use the $pdo connection from init.php
    if (!$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Query to get all customer emails with their names
    // Only get approved customers with valid email addresses
    $sql = "
        SELECT DISTINCT
            a.Email as email,
            CONCAT(ci.firstname, ' ', ci.lastname) as full_name,
            ci.cusID
        FROM accounts a
        INNER JOIN customer_information ci ON a.Id = ci.account_id
        WHERE a.Email IS NOT NULL 
        AND a.Email != ''
        AND ci.Status = 'Approved'
        AND a.Role = 'Customer'
        ORDER BY ci.firstname, ci.lastname
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with customer emails
    echo json_encode([
        'success' => true,
        'data' => $customers,
        'count' => count($customers)
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching customer emails: ' . $e->getMessage()
    ]);
}
?>