<?php
// This is a fixed version of the getOrderDetails function
// Copy this content to replace the problematic function in your order_backend.php

function getOrderDetails()
{
	global $connect;

	$order_id = $_GET['order_id'] ?? null;
	$account_id = $_SESSION['user_id'];

	if (!$order_id) {
		echo json_encode(['success' => false, 'error' => 'Order ID required']);
		if (ob_get_level() > 0) { ob_end_flush(); }
		exit;
	}

	try {
		// First get the cusID from customer_information table using account_id
		$cusStmt = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = ?");
		$cusStmt->execute([$account_id]);
		$customer = $cusStmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$customer) {
			echo json_encode(['success' => false, 'error' => 'Customer profile not found']);
			if (ob_get_level() > 0) { ob_end_flush(); }
			exit;
		}
		
		$customer_id = $customer['cusID'];

		// Check if payment_history table exists
		$tableExists = false;
		try {
			$checkTable = $connect->query("SHOW TABLES LIKE 'payment_history'");
			$tableExists = $checkTable->rowCount() > 0;
		} catch (Exception $e) {
			// Table doesn't exist, continue without payment data
		}

		// Simplified query that only uses orders table and accounts table
		// This avoids the problematic vehicles table columns
		if ($tableExists) {
			$sql = "SELECT 
                        o.order_id as id,
                        o.order_number,
                        o.customer_id,
                        o.sales_agent_id as agent_id,
                        o.vehicle_id,
                        o.vehicle_model as model_name,
                        o.vehicle_variant as variant,
                        o.model_year as year_model,
                        o.base_price,
                        o.discount_amount,
                        o.total_price as total_amount,
                        o.payment_method,
                        o.down_payment,
                        o.financing_term,
                        o.monthly_payment,
                        o.order_status as status,
                        o.delivery_date,
                        o.actual_delivery_date,
                        o.created_at,
                        CONCAT(agent.FirstName, ' ', agent.LastName) as agent_name,
                        agent.Email as agent_email,
                        COALESCE(SUM(ph.amount_paid), 0) as total_paid,
                        (o.total_price - COALESCE(SUM(ph.amount_paid), 0)) as remaining_balance,
                        ROUND((COALESCE(SUM(ph.amount_paid), 0) / o.total_price) * 100, 2) as payment_progress
                    FROM orders o
                    LEFT JOIN accounts agent ON o.sales_agent_id = agent.Id
                    LEFT JOIN payment_history ph ON o.order_id = ph.order_id AND ph.status = 'Confirmed'
                    WHERE o.order_id = ? AND o.customer_id = ?
                    GROUP BY o.order_id, agent.Id";
		} else {
			// Fallback query without payment_history table
			$sql = "SELECT 
                        o.order_id as id,
                        o.order_number,
                        o.customer_id,
                        o.sales_agent_id as agent_id,
                        o.vehicle_id,
                        o.vehicle_model as model_name,
                        o.vehicle_variant as variant,
                        o.model_year as year_model,
                        o.base_price,
                        o.discount_amount,
                        o.total_price as total_amount,
                        o.payment_method,
                        o.down_payment,
                        o.financing_term,
                        o.monthly_payment,
                        o.order_status as status,
                        o.delivery_date,
                        o.actual_delivery_date,
                        o.created_at,
                        CONCAT(agent.FirstName, ' ', agent.LastName) as agent_name,
                        agent.Email as agent_email,
                        0 as total_paid,
                        o.total_price as remaining_balance,
                        0 as payment_progress
                    FROM orders o
                    LEFT JOIN accounts agent ON o.sales_agent_id = agent.Id
                    WHERE o.order_id = ? AND o.customer_id = ?";
		}

		$stmt = $connect->prepare($sql);
		$stmt->execute([$order_id, $customer_id]);
		$order = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$order) {
			echo json_encode(['success' => false, 'error' => 'Order not found']);
			if (ob_get_level() > 0) { ob_end_flush(); }
			exit;
		}

		echo json_encode([
			'success' => true,
			'data' => $order
		]);
		if (ob_get_level() > 0) { ob_end_flush(); }
		exit;
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'error' => 'Failed to fetch order details: ' . $e->getMessage()
		]);
		if (ob_get_level() > 0) { ob_end_flush(); }
		exit;
	}
}

echo "This is the fixed getOrderDetails function.\n";
echo "The issue was that the SQL query was trying to select columns from the vehicles table\n";
echo "that don't exist (like v.brand, v.model, etc.).\n\n";
echo "The fixed version only uses the orders table and accounts table,\n";
echo "which should have the correct column names.\n\n";
echo "To apply this fix, copy the function above and replace the existing\n";
echo "getOrderDetails function in your order_backend.php file.\n";
?>