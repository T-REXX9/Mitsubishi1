<?php
session_start();
include_once(dirname(__DIR__) . '/database/db_conn.php');

// Check if user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_financing_rates':
        getFinancingRates();
        break;
    case 'update_financing_rates':
        updateFinancingRates();
        break;
    case 'get_financing_rules':
        getFinancingRules();
        break;
    case 'update_financing_rules':
        updateFinancingRules();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}

function getFinancingRates()
{
    global $connect;
    
    try {
        $stmt = $connect->prepare("SELECT term_months, annual_rate FROM financing_rates ORDER BY term_months ASC");
        $stmt->execute();
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert decimal rates to percentages for display
        foreach ($rates as &$rate) {
            $rate['annual_rate_percent'] = round($rate['annual_rate'] * 100, 2);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $rates
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch financing rates: ' . $e->getMessage()
        ]);
    }
}

function updateFinancingRates()
{
    global $connect;
    
    $rates = $_POST['rates'] ?? [];
    
    if (empty($rates) || !is_array($rates)) {
        echo json_encode(['success' => false, 'error' => 'Invalid rates data']);
        return;
    }
    
    try {
        $connect->beginTransaction();
        
        foreach ($rates as $rate) {
            $term_months = (int)($rate['term_months'] ?? 0);
            $annual_rate_percent = (float)($rate['annual_rate_percent'] ?? 0);
            
            if ($term_months <= 0 || $annual_rate_percent <= 0 || $annual_rate_percent > 100) {
                throw new Exception('Invalid rate data for term: ' . $term_months);
            }
            
            // Convert percentage to decimal
            $annual_rate_decimal = $annual_rate_percent / 100;
            
            $stmt = $connect->prepare("
                INSERT INTO financing_rates (term_months, annual_rate) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE 
                    annual_rate = VALUES(annual_rate),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$term_months, $annual_rate_decimal]);
        }
        
        $connect->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Financing rates updated successfully'
        ]);
    } catch (Exception $e) {
        $connect->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update financing rates: ' . $e->getMessage()
        ]);
    }
}

function getFinancingRules()
{
    global $connect;
    
    try {
        $stmt = $connect->prepare("
            SELECT id, rule_name, min_down_payment_percent, max_financing_amount, 
                   min_credit_score, is_active 
            FROM financing_rules 
            WHERE is_active = 1 
            ORDER BY id ASC
        ");
        $stmt->execute();
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert decimal percentages to display percentages
        foreach ($rules as &$rule) {
            $rule['min_down_payment_percent_display'] = round($rule['min_down_payment_percent'] * 100, 2);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $rules
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch financing rules: ' . $e->getMessage()
        ]);
    }
}

function updateFinancingRules()
{
    global $connect;
    
    $rule_id = (int)($_POST['rule_id'] ?? 1);
    $min_down_payment_percent = (float)($_POST['min_down_payment_percent'] ?? 20);
    $max_financing_amount = (float)($_POST['max_financing_amount'] ?? 5000000);
    $min_credit_score = $_POST['min_credit_score'] ? (int)$_POST['min_credit_score'] : null;
    
    if ($min_down_payment_percent <= 0 || $min_down_payment_percent > 100) {
        echo json_encode(['success' => false, 'error' => 'Invalid down payment percentage']);
        return;
    }
    
    if ($max_financing_amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid maximum financing amount']);
        return;
    }
    
    try {
        // Convert percentage to decimal
        $min_down_payment_decimal = $min_down_payment_percent / 100;
        
        $stmt = $connect->prepare("
            UPDATE financing_rules 
            SET min_down_payment_percent = ?, 
                max_financing_amount = ?, 
                min_credit_score = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND is_active = 1
        ");
        
        $stmt->execute([
            $min_down_payment_decimal,
            $max_financing_amount,
            $min_credit_score,
            $rule_id
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('No financing rule found to update');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Financing rules updated successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update financing rules: ' . $e->getMessage()
        ]);
    }
}
?>