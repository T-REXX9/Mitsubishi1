<?php
session_start();
require_once '../includes/database/db_conn.php';

// Always return JSON from this endpoint
header('Content-Type: application/json');

// Enable error logging
error_log("🚀 Loan application API started - " . date('Y-m-d H:i:s'));
error_log("📊 Session data: " . json_encode(['user_id' => $_SESSION['user_id'] ?? 'not set']))
;
error_log("📝 POST data keys: " . json_encode(array_keys($_POST)));
error_log("📁 FILES data keys: " . json_encode(array_keys($_FILES)));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("❌ Authentication failed - user not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    error_log("✅ Starting loan application processing");
    
    // Use the global database connection from db_conn.php
    global $connect;
    $pdo = $connect;
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    error_log("✅ Database connection established");
    
    // Get form data
    $customer_id = $_SESSION['user_id'];
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $applicant_type = $_POST['applicant_type'] ?? null;
    
    error_log("📋 Form data extracted: " . json_encode([
        'customer_id' => $customer_id,
        'vehicle_id' => $vehicle_id,
        'applicant_type' => $applicant_type
    ]));
    
    // Validate required fields
    if (!$vehicle_id || !$applicant_type) {
        error_log("❌ Validation failed - missing required fields");
        throw new Exception('Missing required fields: vehicle_id or applicant_type');
    }
    
    error_log("✅ Required field validation passed");
    
    // Handle file uploads
    $uploadDir = '../uploads/loan_documents/';
    if (!file_exists($uploadDir)) {
        error_log("📁 Creating upload directory: " . $uploadDir);
        mkdir($uploadDir, 0777, true);
    }
    
    error_log("📁 Upload directory ready: " . $uploadDir);
    
    $uploadedFiles = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    error_log("📄 Starting file upload processing, files count: " . count($_FILES));
    
    // Process file uploads
    foreach ($_FILES as $fieldName => $file) {
        error_log("📄 Processing file: {$fieldName}, error code: {$file['error']}, size: {$file['size']}, type: {$file['type']}");
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file type
            if (!in_array($file['type'], $allowedTypes)) {
                error_log("❌ Invalid file type for {$fieldName}: {$file['type']}");
                throw new Exception("Invalid file type for {$fieldName}. Only JPG, PNG, and PDF files are allowed.");
            }
            
            // Validate file size
            if ($file['size'] > $maxFileSize) {
                error_log("❌ File too large for {$fieldName}: {$file['size']} bytes");
                throw new Exception("File {$fieldName} is too large. Maximum size is 5MB.");
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = $customer_id . '_' . $vehicle_id . '_' . $fieldName . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            error_log("📄 Generated filename: {$fileName}");
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $uploadedFiles[$fieldName] = $fileName;
                error_log("✅ File uploaded successfully: {$fieldName} -> {$fileName}");
            } else {
                error_log("❌ Failed to move uploaded file: {$fieldName}");
                throw new Exception("Failed to upload {$fieldName}");
            }
        } else {
            error_log("⚠️ File upload error for {$fieldName}: " . $file['error']);
        }
    }
    
    error_log("✅ File upload processing completed, uploaded files: " . json_encode($uploadedFiles));
    
    // Map form field names to database field names (matching original system)
    $field_mapping = [
        'coec_payslip' => 'income_source',
        'itr_2316' => 'itr',
        'itr_1701' => 'itr',
        'proof_remittance' => 'remittance_proof',
        'latest_contract' => 'contract'
    ];

    error_log("🗂️ Field mapping: " . json_encode($field_mapping));

    // Remap uploaded files to match database fields
    $mapped_files = [];
    foreach ($uploadedFiles as $form_key => $fileName) {
        $db_key = $field_mapping[$form_key] ?? $form_key;
        $mapped_files[$db_key] = $fileName;
    }
    
    error_log("🗂️ Mapped files: " . json_encode($mapped_files));
    
    // Get payment plan data from POST request (frontend sends calculated values)
    $paymentPlanData = [
        'down_payment' => $_POST['down_payment'] ?? 0,
        'financing_term' => $_POST['financing_term'] ?? 0,
        'monthly_payment' => $_POST['monthly_payment'] ?? 0,
        'total_amount' => $_POST['total_amount'] ?? 0,
        'interest_rate' => $_POST['interest_rate'] ?? 0
    ];

    error_log("💰 Payment plan data from frontend: " . json_encode($paymentPlanData));
    
    // Fetch vehicle prices for storage consistency
    $vehStmt = $pdo->prepare("SELECT base_price, promotional_price FROM vehicles WHERE id = ?");
    $vehStmt->execute([$vehicle_id]);
    $vehicle = $vehStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        throw new Exception('Vehicle not found');
    }
    
    $base_price = (float)$vehicle['base_price'];
    $promo_price = (float)($vehicle['promotional_price'] ?? 0);
    // Fix promotional price logic: use promotional price only if it's > 0 AND < base_price
    $effective_price = ($promo_price > 0 && $promo_price < $base_price) ? $promo_price : $base_price;
    
    // Recompute payment plan on the server using centralized calculator to ensure consistency
    // This avoids mismatches during approval validation due to frontend/base price discrepancies
    require_once '../includes/backend/loan_backend.php';
    try {
        $serverPlan = computePaymentPlan(
            $pdo,
            $effective_price,
            floatval($paymentPlanData['down_payment']),
            intval($paymentPlanData['financing_term'])
        );
        // Override client-submitted values with authoritative server calculation
        $paymentPlanData['monthly_payment'] = $serverPlan['monthly_payment'];
        $paymentPlanData['total_amount'] = $serverPlan['total_amount'];
        $paymentPlanData['interest_rate'] = $serverPlan['interest_rate_percent'];
        $paymentPlanData['down_payment'] = $serverPlan['down_payment'];
        $paymentPlanData['financing_term'] = $serverPlan['financing_term'];
        error_log("✅ Server-side payment plan computed and applied: " . json_encode($serverPlan));
    } catch (Exception $calcEx) {
        error_log("❌ Server-side payment plan computation failed: " . $calcEx->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid loan terms: ' . $calcEx->getMessage()]);
        exit;
    }
    
    error_log("💰 Vehicle prices - Base: {$base_price}, Promo: {$promo_price}, Effective: {$effective_price}");

    // Construct SQL INSERT statement with payment plan fields and vehicle prices
    $sql = "INSERT INTO loan_applications (
        customer_id, vehicle_id, vehicle_base_price, vehicle_promotional_price, vehicle_effective_price,
        applicant_type, application_date, status,
        down_payment, financing_term, monthly_payment, total_amount, interest_rate,
        valid_id_file, valid_id_filename, valid_id_type,
        income_source_file, income_source_filename, income_source_type,
        employment_certificate_file, employment_certificate_filename, employment_certificate_type,
        payslip_file, payslip_filename, payslip_type,
        company_id_file, company_id_filename, company_id_type,
        itr_file, itr_filename, itr_type,
        bank_statement_file, bank_statement_filename, bank_statement_type,
        dti_permit_file, dti_permit_filename, dti_permit_type,
        proof_billing_file, proof_billing_filename, proof_billing_type,
        remittance_proof_file, remittance_proof_filename, remittance_proof_type,
        contract_file, contract_filename, contract_type,
        spa_file, spa_filename, spa_type,
        ada_pdc_file, ada_pdc_filename, ada_pdc_type,
        updated_at
     ) VALUES (
        :customer_id, :vehicle_id, :vehicle_base_price, :vehicle_promotional_price, :vehicle_effective_price,
        :applicant_type, NOW(), 'Pending',
        :down_payment, :financing_term, :monthly_payment, :total_amount, :interest_rate,
        :valid_id_file, :valid_id_filename, :valid_id_type,
        :income_source_file, :income_source_filename, :income_source_type,
        :employment_certificate_file, :employment_certificate_filename, :employment_certificate_type,
        :payslip_file, :payslip_filename, :payslip_type,
        :company_id_file, :company_id_filename, :company_id_type,
        :itr_file, :itr_filename, :itr_type,
        :bank_statement_file, :bank_statement_filename, :bank_statement_type,
        :dti_permit_file, :dti_permit_filename, :dti_permit_type,
        :proof_billing_file, :proof_billing_filename, :proof_billing_type,
        :remittance_proof_file, :remittance_proof_filename, :remittance_proof_type,
        :contract_file, :contract_filename, :contract_type,
        :spa_file, :spa_filename, :spa_type,
        :ada_pdc_file, :ada_pdc_filename, :ada_pdc_type,
        NOW()
     )";
    
    error_log("🗄️ SQL prepared: " . substr($sql, 0, 200) . "...");
    
    // Get file data for database insertion (matching original system structure)
    $getFileData = function($fileKey) use ($mapped_files, $uploadDir) {
        if (isset($mapped_files[$fileKey])) {
            $filePath = $uploadDir . $mapped_files[$fileKey];
            return [
                'data' => file_get_contents($filePath),
                'name' => $mapped_files[$fileKey],
                'type' => mime_content_type($filePath)
            ];
        }
        return ['data' => null, 'name' => null, 'type' => null];
    };
    
    // Get mapped file data
    $validId = $getFileData('valid_id');
    $incomeSource = $getFileData('income_source');
    $employmentCert = $getFileData('employment_certificate');
    $payslip = $getFileData('payslip');
    $companyId = $getFileData('company_id');
    $itr = $getFileData('itr');
    $bankStatement = $getFileData('bank_statement');
    $dtiPermit = $getFileData('dti_permit');
    $proofBilling = $getFileData('proof_billing');
    $remittanceProof = $getFileData('remittance_proof');
    $contract = $getFileData('contract');
    $spa = $getFileData('spa');
    $adaPdc = $getFileData('ada_pdc');
    
    // Execute the query with file data and payment plan data using named placeholders
    error_log("🗄️ Preparing SQL statement");
    $stmt = $pdo->prepare($sql);
    
    // Bind scalar values
    $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindValue(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
    $stmt->bindValue(':vehicle_base_price', $base_price);
    $stmt->bindValue(':vehicle_promotional_price', ($promo_price > 0 && $promo_price < $base_price) ? $promo_price : null);
    $stmt->bindValue(':vehicle_effective_price', $effective_price);
    $stmt->bindValue(':applicant_type', $applicant_type);
    
    $stmt->bindValue(':down_payment', $paymentPlanData['down_payment'] ?? 0);
    $stmt->bindValue(':financing_term', $paymentPlanData['financing_term'] ?? 0);
    $stmt->bindValue(':monthly_payment', $paymentPlanData['monthly_payment'] ?? 0);
    $stmt->bindValue(':total_amount', $paymentPlanData['total_amount'] ?? 0);
    $stmt->bindValue(':interest_rate', $paymentPlanData['interest_rate'] ?? 0);
    
    // Map of document keys to their data arrays
    $docMap = [
    'valid_id' => $validId,
    'income_source' => $incomeSource,
    'employment_certificate' => $employmentCert,
    'payslip' => $payslip,
    'company_id' => $companyId,
    'itr' => $itr,
    'bank_statement' => $bankStatement,
    'dti_permit' => $dtiPermit,
    'proof_billing' => $proofBilling,
    'remittance_proof' => $remittanceProof,
    'contract' => $contract,
    'spa' => $spa,
    'ada_pdc' => $adaPdc,
    ];
    
    foreach ($docMap as $key => $file) {
        // Bind file data; if null, bind as NULL explicitly
        if ($file['data'] === null) {
            $stmt->bindValue(":{$key}_file", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":{$key}_file", $file['data'], PDO::PARAM_LOB);
        }
        if ($file['name'] === null) {
            $stmt->bindValue(":{$key}_filename", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":{$key}_filename", $file['name']);
        }
        if ($file['type'] === null) {
            $stmt->bindValue(":{$key}_type", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":{$key}_type", $file['type']);
        }
    }

    // Dump the bound parameters for diagnostics
    ob_start();
    $stmt->debugDumpParams();
    $dump = ob_get_clean();
    error_log("🧪 PDO debugDumpParams:\n" . $dump);
    
    error_log("🗄️ Executing SQL with named parameters");
    $result = $stmt->execute();

    if ($result === false) {
        $info = $stmt->errorInfo();
        error_log("❌ PDO execute failed: SQLSTATE={$info[0]} DRIVER_CODE={$info[1]} MSG={$info[2]}");
    }
    
    error_log("🗄️ SQL execution result: " . ($result ? 'success' : 'failed'));
    error_log("🗄️ Affected rows: " . $stmt->rowCount());
    
    if ($stmt->rowCount() > 0) {
        $applicationId = $pdo->lastInsertId();
        error_log("✅ Loan application inserted successfully with ID: " . $applicationId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Loan application submitted successfully',
            'application_id' => $applicationId
        ]);
    } else {
        error_log("❌ No rows affected during insertion");
        throw new Exception('Failed to insert loan application');
    }
    
} catch (Exception $e) {
    error_log("💥 Exception caught: " . $e->getMessage());
    error_log("💥 Exception trace: " . $e->getTraceAsString());
    
    // Clean up uploaded files on error
    if (isset($uploadedFiles)) {
        error_log("🧹 Cleaning up uploaded files: " . count($uploadedFiles));
        foreach ($uploadedFiles as $fileName) {
            $filePath = (isset($uploadDir) ? $uploadDir : '../uploads/loan_documents/') . $fileName;
            if (file_exists($filePath)) {
                unlink($filePath);
                error_log("🗑️ Deleted file: " . $fileName);
            }
        }
    }
    
    http_response_code(500);
    $errorResponse = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    error_log("📤 Sending error response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
}

error_log("🏁 Loan application API completed");
?>