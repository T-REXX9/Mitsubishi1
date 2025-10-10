<?php
/**
 * Centralized Payment Plan Calculator
 * 
 * This calculator provides consistent payment calculations across the entire system:
 * - Quotations
 * - Loan applications
 * - Loan approvals
 * - Order management
 * 
 * Usage:
 *   include_once('payment_calculator.php');
 *   $calculator = new PaymentCalculator($pdo);
 *   $result = $calculator->calculatePlan($vehiclePrice, $downPayment, $financingTerm);
 */

class PaymentCalculator {
    private $pdo;
    private $financingRates;
    private $minDownPaymentPercent;
    
    public function __construct($databaseConnection) {
        $this->pdo = $databaseConnection;
        $this->loadFinancingConfig();
    }
    
    /**
     * Load financing configuration from database
     */
    private function loadFinancingConfig() {
        // Load financing rates
        try {
            $stmt = $this->pdo->prepare("SELECT term_months, annual_rate FROM financing_rates ORDER BY term_months ASC");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($rows && count($rows) > 0) {
                $this->financingRates = [];
                foreach ($rows as $row) {
                    $this->financingRates[(int)$row['term_months']] = (float)$row['annual_rate'];
                }
            } else {
                // Default rates if table doesn't exist
                $this->financingRates = $this->getDefaultRates();
            }
        } catch (Exception $e) {
            $this->financingRates = $this->getDefaultRates();
        }
        
        // Load minimum down payment percent
        try {
            $stmt = $this->pdo->prepare("SELECT min_down_payment_percent FROM financing_rules ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && isset($row['min_down_payment_percent'])) {
                $val = (float)$row['min_down_payment_percent'];
                // Stored as decimal (0.20) or percent (20.0)
                if ($val > 0 && $val <= 1.0) {
                    $this->minDownPaymentPercent = $val;
                } elseif ($val > 1.0 && $val <= 100.0) {
                    $this->minDownPaymentPercent = $val / 100.0;
                } else {
                    $this->minDownPaymentPercent = 0.20; // default 20%
                }
            } else {
                $this->minDownPaymentPercent = 0.20; // default 20%
            }
        } catch (Exception $e) {
            $this->minDownPaymentPercent = 0.20; // default 20%
        }
    }
    
    /**
     * Get default financing rates
     */
    private function getDefaultRates() {
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
     * Calculate amortization schedule
     */
    public function calculateAmortization($principal, $annualRate, $months) {
        if ($principal <= 0 || $months <= 0) {
            return [
                'monthly_payment' => 0.0,
                'total_payments' => 0.0,
                'total_interest' => 0.0,
                'schedule' => []
            ];
        }
        
        $monthlyRate = $annualRate > 0 ? ($annualRate / 12.0) : 0.0;
        if ($monthlyRate > 0) {
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
        } else {
            $monthlyPayment = $principal / $months;
        }
        
        $schedule = [];
        $remainingBalance = $principal;
        
        for ($i = 1; $i <= $months; $i++) {
            $interestPayment = $remainingBalance * $monthlyRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            $remainingBalance = max(0.0, $remainingBalance - $principalPayment);
            
            $schedule[] = [
                'payment_number' => $i,
                'monthly_payment' => round($monthlyPayment, 2),
                'principal_payment' => round($principalPayment, 2),
                'interest_payment' => round($interestPayment, 2),
                'remaining_balance' => round($remainingBalance, 2)
            ];
        }
        
        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payments' => round($monthlyPayment * $months, 2),
            'total_interest' => round(($monthlyPayment * $months) - $principal, 2),
            'schedule' => $schedule
        ];
    }
    
    /**
     * Calculate payment plan with validation
     */
    public function calculatePlan($vehiclePrice, $downPayment, $financingTerm) {
        // Validate inputs
        if ($vehiclePrice <= 0) {
            throw new Exception('Invalid vehicle price');
        }
        
        if ($financingTerm <= 0) {
            throw new Exception('Invalid financing term');
        }
        
        // Validate financing term
        $allowedTerms = array_keys($this->financingRates);
        if (!in_array($financingTerm, $allowedTerms, true)) {
            throw new Exception('Unsupported financing term. Allowed terms: ' . implode(', ', $allowedTerms) . ' months');
        }
        
        // Validate down payment
        $minDown = round($vehiclePrice * $this->minDownPaymentPercent, 2);
        if ($downPayment < $minDown) {
            $percentStr = (string)round($this->minDownPaymentPercent * 100, 2) . '%';
            throw new Exception('Down payment must be at least ' . $percentStr . ' of the vehicle price (minimum ₱' . number_format($minDown, 2) . ')');
        }
        
        if ($downPayment > $vehiclePrice) {
            throw new Exception('Down payment cannot exceed the vehicle price');
        }
        
        // Calculate loan amount
        $loanAmount = max(0.0, $vehiclePrice - $downPayment);
        $annualRate = $this->financingRates[$financingTerm];
        
        // Calculate amortization
        $amortization = $this->calculateAmortization($loanAmount, $annualRate, $financingTerm);
        
        // Calculate total amount (down payment + total loan payments)
        $totalAmount = round($downPayment + $amortization['total_payments'], 2);
        
        return [
            'vehicle_price' => round($vehiclePrice, 2),
            'down_payment' => round($downPayment, 2),
            'loan_amount' => round($loanAmount, 2),
            'financing_term' => $financingTerm,
            'monthly_payment' => $amortization['monthly_payment'],
            'total_amount' => $totalAmount,
            'total_interest' => $amortization['total_interest'],
            'interest_rate_percent' => round($annualRate * 100, 3),
            'amortization_schedule' => $amortization['schedule']
        ];
    }
    
    /**
     * Get available financing terms
     */
    public function getAvailableTerms() {
        return array_keys($this->financingRates);
    }
    
    /**
     * Get financing rate for a term
     */
    public function getRateForTerm($term) {
        return isset($this->financingRates[$term]) ? $this->financingRates[$term] : null;
    }
    
    /**
     * Get minimum down payment percent
     */
    public function getMinDownPaymentPercent() {
        return $this->minDownPaymentPercent;
    }
    
    /**
     * Get financing configuration for display
     */
    public function getConfigForDisplay() {
        $config = [];
        foreach ($this->financingRates as $term => $rate) {
            $config[] = [
                'term_months' => $term,
                'annual_rate_percent' => round($rate * 100, 3),
                'display_text' => $term . ' months (' . round($rate * 100, 2) . '%)'
            ];
        }
        return $config;
    }
}

// API endpoint for calculator
if (basename($_SERVER['SCRIPT_NAME']) === 'payment_calculator.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Include database connection
        include_once(__DIR__ . '/init.php');
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        $calculator = new PaymentCalculator($pdo);
        
        switch ($action) {
            case 'calculate':
                $vehiclePrice = floatval($input['vehicle_price'] ?? 0);
                $downPayment = floatval($input['down_payment'] ?? 0);
                $financingTerm = intval($input['financing_term'] ?? 0);
                
                $result = $calculator->calculatePlan($vehiclePrice, $downPayment, $financingTerm);
                
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
                break;
                
            case 'get_config':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'terms' => $calculator->getAvailableTerms(),
                        'rates' => $calculator->getConfigForDisplay(),
                        'min_down_payment_percent' => $calculator->getMinDownPaymentPercent() * 100
                    ]
                ]);
                break;
                
            case 'validate':
                // Validate loan application data
                $vehiclePrice = floatval($input['vehicle_price'] ?? 0);
                $downPayment = floatval($input['down_payment'] ?? 0);
                $financingTerm = intval($input['financing_term'] ?? 0);
                
                try {
                    $result = $calculator->calculatePlan($vehiclePrice, $downPayment, $financingTerm);
                    echo json_encode([
                        'success' => true,
                        'valid' => true,
                        'message' => 'Loan terms are valid',
                        'data' => $result
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => true,
                        'valid' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                break;
                
            case 'verify_payment':
                // Verify existing payment data against recalculated values
                $vehiclePrice = floatval($input['vehicle_price'] ?? 0);
                $downPayment = floatval($input['down_payment'] ?? 0);
                $financingTerm = intval($input['financing_term'] ?? 0);
                $existingMonthlyPayment = floatval($input['existing_monthly_payment'] ?? 0);
                $tolerance = floatval($input['tolerance'] ?? 1.0); // Default 1 peso tolerance
                
                try {
                    $recalculated = $calculator->calculatePlan($vehiclePrice, $downPayment, $financingTerm);
                    $difference = abs($recalculated['monthly_payment'] - $existingMonthlyPayment);
                    $isMismatch = $difference > $tolerance;
                    
                    echo json_encode([
                        'success' => true,
                        'mismatch_detected' => $isMismatch,
                        'difference' => $difference,
                        'tolerance' => $tolerance,
                        'existing_payment' => $existingMonthlyPayment,
                        'recalculated_payment' => $recalculated['monthly_payment'],
                        'recalculated_data' => $recalculated,
                        'message' => $isMismatch ? 
                            "Payment mismatch detected. Difference: ₱{$difference}" : 
                            'Payment calculations match within tolerance'
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Payment verification failed: ' . $e->getMessage()
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Only show documentation when accessed directly via browser (not API calls)
if (basename($_SERVER['SCRIPT_NAME']) === 'payment_calculator.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "Centralized Payment Calculator\n";
    echo "============================\n";
    echo "This is a centralized payment calculator for the Mitsubishi system.\n";
    echo "It provides consistent payment calculations across quotations, loan applications, approvals, and order management.\n\n";
    echo "To use this calculator in your PHP code:\n";
    echo "  include_once('payment_calculator.php');\n";
    echo "  \$calculator = new PaymentCalculator(\$pdo);\n";
    echo "  \$result = \$calculator->calculatePlan(\$vehiclePrice, \$downPayment, \$financingTerm);\n\n";
    echo "To use via API:\n";
    echo "  POST to this file with JSON data containing action and parameters.\n";
}
?>