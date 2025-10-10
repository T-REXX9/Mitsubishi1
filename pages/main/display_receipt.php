<?php
include_once(dirname(dirname(__DIR__)) . '/includes/init.php');

// Check if user is logged in and has proper role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Admin', 'SalesAgent'])) {
    http_response_code(401);
    header('Content-Type: text/plain');
    echo 'Unauthorized access';
    exit();
}

// Get PMS ID from request
$pmsId = $_GET['pms_id'] ?? 0;

if (!$pmsId) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Invalid PMS ID';
    exit();
}

try {
    // Use the database connection from init.php
    $pdo = $GLOBALS['pdo'] ?? null;
    
    if (!$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Fetch the receipt image
    $query = "SELECT uploaded_receipt FROM car_pms_records WHERE pms_id = :pms_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['pms_id' => $pmsId]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !$result['uploaded_receipt']) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Receipt not found';
        exit();
    }
    
    // Detect image type from the blob data
    $imageData = $result['uploaded_receipt'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);
    
    // Validate that it's an image
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(415);
        header('Content-Type: text/plain');
        echo 'Unsupported file type';
        exit();
    }
    
    // Set appropriate headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: public, max-age=86400'); // Cache for 1 day
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    
    // Output the image
    echo $imageData;
    
} catch (Exception $e) {
    error_log("Error displaying receipt: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error loading receipt';
}
?>
