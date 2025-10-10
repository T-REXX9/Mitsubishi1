<?php
session_start();
header('Content-Type: application/json');
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');
include_once(dirname(__DIR__) . '/includes/backend/loan_backend.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get form data
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $applicant_type = $_POST['applicantType'] ?? 'EMPLOYED';
    // Client-provided values (will be validated/recomputed server-side)
    $loan_amount = $_POST['loanAmount'] ?? 0; // ignored in favor of effective price
    $down_payment = (float)($_POST['downPayment'] ?? 0);
    $financing_term = (int)($_POST['financingTerm'] ?? 12);
    $monthly_payment = 0; // will compute
    $total_amount = 0; // will compute
    $interest_rate = 0; // will compute (percent)
    $annual_income = $_POST['annualIncome'] ?? 0;
    $employment_status = $_POST['employmentStatus'] ?? '';
    $employer_name = $_POST['employerName'] ?? '';
    $employment_years = $_POST['employmentYears'] ?? 0;
    $monthly_income = $_POST['monthlyIncome'] ?? 0;
    $other_income = $_POST['otherIncome'] ?? 0;
    $monthly_expenses = $_POST['monthlyExpenses'] ?? 0;
    $existing_loans = $_POST['existingLoans'] ?? 0;
    $credit_cards = $_POST['creditCards'] ?? 0;
    $dependents = $_POST['dependents'] ?? 0;
    $marital_status = $_POST['maritalStatus'] ?? 'Single';
    $spouse_income = $_POST['spouseIncome'] ?? 0;
    $home_ownership = $_POST['homeOwnership'] ?? 'Rented';
    $years_current_address = $_POST['yearsCurrentAddress'] ?? 0;
    $references = $_POST['references'] ?? '';

    if (!$vehicle_id) {
        throw new Exception('Vehicle ID is required');
    }

    // Validate applicant type
    if (!in_array($applicant_type, ['EMPLOYED', 'BUSINESS', 'OFW'])) {
        $applicant_type = 'EMPLOYED';
    }

    // --- Server-side payment plan calculation using centralized helper ---
    // Fetch vehicle effective price (promotional if set and lower, else base price)
    $vehStmt = $connect->prepare("SELECT base_price, promotional_price FROM vehicles WHERE id = ?");
    $vehStmt->execute([$vehicle_id]);
    $vehicle = $vehStmt->fetch(PDO::FETCH_ASSOC);
    if (!$vehicle || ((!$vehicle['base_price'] || (float)$vehicle['base_price'] <= 0) && (!$vehicle['promotional_price'] || (float)$vehicle['promotional_price'] <= 0))) {
        throw new Exception('Vehicle price information not found');
    }
    $base_price = (float)$vehicle['base_price'];
    $promo_price = (float)($vehicle['promotional_price'] ?? 0);
    $effective_price = ($vehicle['promotional_price'] && $vehicle['promotional_price'] > 0 && $vehicle['promotional_price'] < $vehicle['base_price']) 
                        ? $vehicle['promotional_price'] 
                        : $vehicle['base_price'];

    // Compute payment plan with validation (throws on invalid)
    $plan = computePaymentPlan($connect, $effective_price, $down_payment, $financing_term);

    // Override with server-calculated values
    $loan_amount = $plan['loan_amount'];
    $down_payment = $plan['down_payment'];
    $financing_term = $plan['financing_term'];
    $monthly_payment = $plan['monthly_payment'];
    $total_amount = $plan['total_amount'];
    $interest_rate = $plan['interest_rate_percent'];

    // Store loan application data in session for use in document submission
    $_SESSION['loan_application_data'] = [
        'vehicle_id' => $vehicle_id,
        'applicant_type' => $applicant_type,
        'loan_amount' => $loan_amount,
        'down_payment' => $down_payment,
        'financing_term' => $financing_term,
        'monthly_payment' => $monthly_payment,
        'total_amount' => $total_amount,
        'interest_rate' => $interest_rate,
        'annual_income' => $annual_income,
        'employment_status' => $employment_status,
        'employer_name' => $employer_name,
        'employment_years' => $employment_years,
        'monthly_income' => $monthly_income,
        'other_income' => $other_income,
        'monthly_expenses' => $monthly_expenses,
        'existing_loans' => $existing_loans,
        'credit_cards' => $credit_cards,
        'dependents' => $dependents,
        'marital_status' => $marital_status,
        'spouse_income' => $spouse_income,
        'home_ownership' => $home_ownership,
        'years_current_address' => $years_current_address,
        'references' => $references,
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Save to temporary applications table with vehicle pricing information
    $stmt = $connect->prepare("\n        INSERT INTO loan_applications_temp (\n            customer_id, vehicle_id, vehicle_base_price, vehicle_promotional_price, vehicle_effective_price,\n            applicant_type, loan_amount, down_payment,\n            financing_term, monthly_payment, total_amount, interest_rate, annual_income, employment_status,\n            employer_name, employment_years, monthly_income, other_income,\n            monthly_expenses, existing_loans, credit_cards, dependents,\n            marital_status, spouse_income, home_ownership, years_current_address,\n            reference_contacts\n         ) VALUES (\n            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?\n        ) ON DUPLICATE KEY UPDATE\n            vehicle_base_price = VALUES(vehicle_base_price),\n            vehicle_promotional_price = VALUES(vehicle_promotional_price),\n            vehicle_effective_price = VALUES(vehicle_effective_price),\n            applicant_type = VALUES(applicant_type),\n            loan_amount = VALUES(loan_amount),\n            down_payment = VALUES(down_payment),\n            financing_term = VALUES(financing_term),\n            monthly_payment = VALUES(monthly_payment),\n            total_amount = VALUES(total_amount),\n            interest_rate = VALUES(interest_rate),\n            annual_income = VALUES(annual_income),\n            employment_status = VALUES(employment_status),\n            employer_name = VALUES(employer_name),\n            employment_years = VALUES(employment_years),\n            monthly_income = VALUES(monthly_income),\n            other_income = VALUES(other_income),\n            monthly_expenses = VALUES(monthly_expenses),\n            existing_loans = VALUES(existing_loans),\n            credit_cards = VALUES(credit_cards),\n            dependents = VALUES(dependents),\n            marital_status = VALUES(marital_status),\n            spouse_income = VALUES(spouse_income),\n            home_ownership = VALUES(home_ownership),\n            years_current_address = VALUES(years_current_address),\n            reference_contacts = VALUES(reference_contacts),\n            updated_at = NOW()\n    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $vehicle_id,
        $base_price,
        $promo_price > 0 ? $promo_price : null,
        $effective_price,
        $applicant_type,
        $loan_amount,
        $down_payment,
        $financing_term,
        $monthly_payment,
        $total_amount,
        $interest_rate,
        $annual_income,
        $employment_status,
        $employer_name,
        $employment_years,
        $monthly_income,
        $other_income,
        $monthly_expenses,
        $existing_loans,
        $credit_cards,
        $dependents,
        $marital_status,
        $spouse_income,
        $home_ownership,
        $years_current_address,
        $references
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Loan application data saved successfully',
        'next_step' => 'document_submission',
        'computed' => [
            'loan_amount' => $loan_amount,
            'down_payment' => $down_payment,
            'financing_term' => $financing_term,
            'monthly_payment' => $monthly_payment,
            'total_amount' => $total_amount,
            'interest_rate' => $interest_rate
        ]
    ]);

} catch (Exception $e) {
    // Validation or other error
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>