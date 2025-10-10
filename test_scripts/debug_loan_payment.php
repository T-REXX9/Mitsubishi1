<?php
// Debug loan application payment calculation
header('Content-Type: text/plain');

echo "=== LOAN APPLICATION PAYMENT DEBUG ===\n\n";

// Include required files
include_once(dirname(__DIR__) . '/includes/init.php');
include_once(dirname(__DIR__) . '/includes/backend/loan_backend.php');

if (!$pdo) {
    die("Database connection failed");
}

// Get application ID from query parameter or use a test ID
$applicationId = $_GET['id'] ?? 1;

echo "Debugging application ID: $applicationId\n\n";

try {
    // Get loan application details
    $loanStmt = $pdo->prepare("SELECT la.*, v.base_price, v.promotional_price 
                                 FROM loan_applications la 
                                 JOIN vehicles v ON la.vehicle_id = v.id 
                                 WHERE la.id = ?");
    $loanStmt->execute([$applicationId]);
    $loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);

    if (!$loanData) {
        die("Loan application not found");
    }

    echo "=== LOAN APPLICATION DATA ===\n";
    echo "ID: {$loanData['id']}\n";
    echo "Status: {$loanData['status']}\n";
    echo "Base Price: {$loanData['base_price']}\n";
    echo "Promotional Price: {$loanData['promotional_price']}\n";
    echo "Down Payment: {$loanData['down_payment']}\n";
    echo "Financing Term: {$loanData['financing_term']}\n";
    echo "Stored Monthly Payment: {$loanData['monthly_payment']}\n";
    echo "Interest Rate: {$loanData['interest_rate']}\n\n";

    // Determine effective price
    $effectivePrice = ($loanData['promotional_price'] && $loanData['promotional_price'] > 0 && $loanData['promotional_price'] < $loanData['base_price']) 
                        ? $loanData['promotional_price'] 
                        : $loanData['base_price'];

    echo "=== PRICE CALCULATION ===\n";
    echo "Effective Price: $effectivePrice\n\n";

    // Calculate payment using the same method as in approveApplicationEnhanced
    echo "=== PAYMENT CALCULATION ===\n";
    echo "Using computePaymentPlan function...\n";
    
    $validationResult = computePaymentPlan(
        $pdo,
        $effectivePrice,
        $loanData['down_payment'],
        $loanData['financing_term']
    );
    
    echo "Calculated Results:\n";
    echo "  Loan Amount: {$validationResult['loan_amount']}\n";
    echo "  Down Payment: {$validationResult['down_payment']}\n";
    echo "  Financing Term: {$validationResult['financing_term']}\n";
    echo "  Monthly Payment: {$validationResult['monthly_payment']}\n";
    echo "  Total Amount: {$validationResult['total_amount']}\n";
    echo "  Interest Rate: {$validationResult['interest_rate_percent']}%\n\n";

    // Compare with stored payment
    echo "=== PAYMENT COMPARISON ===\n";
    echo "Stored Payment: {$loanData['monthly_payment']}\n";
    echo "Calculated Payment: {$validationResult['monthly_payment']}\n";
    
    $storedPayment = floatval($loanData['monthly_payment']);
    $calculatedPayment = $validationResult['monthly_payment'];
    $difference = abs($calculatedPayment - $storedPayment);
    
    echo "Difference: $difference\n";
    
    if ($difference > 1.0) {
        echo "❌ PAYMENT MISMATCH DETECTED!\n";
        echo "This would cause the error you're seeing.\n\n";
        
        echo "=== POSSIBLE CAUSES ===\n";
        echo "1. Different interest rate used in calculation\n";
        echo "2. Different financing term\n";
        echo "3. Different down payment amount\n";
        echo "4. Different effective price (base vs promotional)\n";
        echo "5. Database corruption or manual data changes\n\n";
        
        echo "=== SOLUTION OPTIONS ===\n";
        echo "1. Update the application with the correct calculated payment\n";
        echo "2. Adjust the tolerance in the approval function\n";
        echo "3. Investigate why the original calculation was different\n";
    } else {
        echo "✅ Payments match within tolerance\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>