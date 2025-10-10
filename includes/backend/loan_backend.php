<?php
// Financing and Loan helper utilities (no session start; safe to include anywhere)

/**
 * Get financing rates mapping from DB if available; otherwise default mapping.
 * Returns associative array: term_months => annual_rate_decimal (e.g., 0.105 for 10.5%)
 */
function getFinancingRates($connect)
{
    try {
        $stmt = $connect->prepare("SELECT term_months, annual_rate FROM financing_rates ORDER BY term_months ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows && count($rows) > 0) {
            $map = [];
            foreach ($rows as $r) {
                $term = (int)$r['term_months'];
                $rate = (float)$r['annual_rate']; // stored as decimal (e.g., 0.105)
                $map[$term] = $rate;
            }
            return $map;
        }
    } catch (Throwable $e) {
        // Fallback to defaults if table doesn't exist or query fails
    }

    // Defaults (decimal form)
    return [
        3 => 0.085,
        6 => 0.090,
        12 => 0.105,
        24 => 0.120,
        36 => 0.135,
        48 => 0.150,
        60 => 0.165
    ];
}

/**
 * Get minimum down payment percent as decimal (e.g., 0.20)
 */
function getMinDownPaymentPercent($connect)
{
    try {
        $stmt = $connect->prepare("SELECT min_down_payment_percent FROM financing_rules ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['min_down_payment_percent'])) {
            $val = (float)$row['min_down_payment_percent'];
            // Stored as decimal (0.20)
            if ($val > 0 && $val < 1.0) {
                return $val;
            }
            // If mistakenly stored as percent like 20 or 20.0, convert
            if ($val >= 1.0 && $val <= 100.0) {
                return $val / 100.0;
            }
        }
    } catch (Throwable $e) {
        // ignore and fallback
    }
    return 0.20; // default 20%
}

/**
 * Calculate amortization for given principal, annual rate (decimal), and months.
 * Returns array with monthly_payment, total_payments, total_interest, schedule
 */
function calculateLoanAmortization($principal, $annual_rate_decimal, $months)
{
    if ($principal <= 0 || $months <= 0) {
        return [
            'monthly_payment' => 0.0,
            'total_payments' => 0.0,
            'total_interest' => 0.0,
            'schedule' => []
        ];
    }

    $monthly_rate = $annual_rate_decimal > 0 ? ($annual_rate_decimal / 12.0) : 0.0;
    if ($monthly_rate > 0) {
        $monthly_payment = $principal * ($monthly_rate * pow(1 + $monthly_rate, $months)) / (pow(1 + $monthly_rate, $months) - 1);
    } else {
        $monthly_payment = $principal / $months;
    }

    $schedule = [];
    $remaining_balance = $principal;

    for ($i = 1; $i <= $months; $i++) {
        $interest_payment = $remaining_balance * $monthly_rate;
        $principal_payment = $monthly_payment - $interest_payment;
        $remaining_balance = max(0.0, $remaining_balance - $principal_payment);
        $schedule[] = [
            'payment_number' => $i,
            'monthly_payment' => round($monthly_payment, 2),
            'principal_payment' => round($principal_payment, 2),
            'interest_payment' => round($interest_payment, 2),
            'remaining_balance' => round($remaining_balance, 2)
        ];
    }

    return [
        'monthly_payment' => round($monthly_payment, 2),
        'total_payments' => round($monthly_payment * $months, 2),
        'total_interest' => round(($monthly_payment * $months) - $principal, 2),
        'schedule' => $schedule
    ];
}

/**
 * Compute payment plan, validating down payment and term against config.
 * Returns array: [loan_amount, down_payment, term_months, monthly_payment, total_amount, interest_rate_percent]
 * Throws Exception on validation errors.
 */
function computePaymentPlan($connect, $effective_price, $down_payment, $financing_term)
{
    // Use centralized PaymentCalculator for consistency
    require_once dirname(__DIR__) . '/payment_calculator.php';
    
    $calculator = new PaymentCalculator($connect);
    $result = $calculator->calculatePlan($effective_price, $down_payment, $financing_term);
    
    // Return in the expected format for backward compatibility
    return [
        'loan_amount' => $result['loan_amount'],
        'down_payment' => $result['down_payment'],
        'financing_term' => $result['financing_term'],
        'monthly_payment' => $result['monthly_payment'],
        'total_amount' => $result['total_amount'],
        'interest_rate_percent' => $result['interest_rate_percent']
    ];
}

// API handling for loan backend operations - only handle if this file is directly requested or not skipped
if (!isset($SKIP_LOAN_API_PROCESSING) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && basename($_SERVER['SCRIPT_NAME']) === 'loan_backend.php') {
    require_once dirname(__DIR__) . '/database/db_conn.php';
    
    $action = $_GET['action'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'recalculate':
            try {
                $vehicle_price = floatval($input['vehicle_price'] ?? 0);
                $down_payment = floatval($input['down_payment'] ?? 0);
                $financing_term = intval($input['financing_term'] ?? 0);
                $annual_rate = floatval($input['annual_rate'] ?? 0);
                
                if ($vehicle_price <= 0) {
                    throw new Exception('Invalid vehicle price');
                }
                
                $result = computePaymentPlan($connect, $vehicle_price, $down_payment, $financing_term);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'monthly_payment' => $result['monthly_payment'],
                        'total_amount' => $result['total_amount'],
                        'loan_amount' => $result['loan_amount'],
                        'interest_rate' => $result['interest_rate_percent']
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'amortization':
            try {
                $loan_amount = floatval($input['loan_amount'] ?? 0);
                $annual_rate = floatval($input['annual_rate'] ?? 0);
                $financing_term = intval($input['financing_term'] ?? 0);
                
                if ($loan_amount <= 0 || $financing_term <= 0) {
                    throw new Exception('Invalid loan parameters');
                }
                
                $annual_rate_decimal = $annual_rate / 100; // Convert percentage to decimal
                $schedule = calculateLoanAmortization($loan_amount, $annual_rate_decimal, $financing_term);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'schedule' => $schedule['schedule'],
                        'monthly_payment' => $schedule['monthly_payment'],
                        'total_interest' => $schedule['total_interest'],
                        'total_payments' => $schedule['total_payments']
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'validate':
            try {
                $application_id = intval($input['application_id'] ?? 0);
                
                if (!$application_id) {
                    throw new Exception('Invalid application ID');
                }
                
                // Get application details (includes stored vehicle_effective_price)
                $stmt = $connect->prepare("SELECT la.*, v.base_price, v.promotional_price 
                                         FROM loan_applications la 
                                         JOIN vehicles v ON la.vehicle_id = v.id 
                                         WHERE la.id = ?");
                $stmt->execute([$application_id]);
                $app = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$app) {
                    throw new Exception('Application not found');
                }
                
                // Prioritize stored effective price from the application for consistency
                if (isset($app['vehicle_effective_price']) && (float)$app['vehicle_effective_price'] > 0) {
                    $vehicle_price = (float)$app['vehicle_effective_price'];
                } else {
                    // Fallback to current vehicle pricing if not stored
                    $vehicle_price = ($app['promotional_price'] && $app['promotional_price'] > 0 && $app['promotional_price'] < $app['base_price']) 
                                   ? $app['promotional_price'] 
                                   : $app['base_price'];
                }
                
                $result = computePaymentPlan(
                    $connect,
                    $vehicle_price,
                    $app['down_payment'],
                    $app['financing_term']
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Application validation passed',
                    'data' => $result
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    exit;
}