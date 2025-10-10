<?php
// Chat helper functions

function assignCustomerToAgent($customer_id, $pdo)
{
	// Get available agent with least load
	$stmt = $pdo->prepare("
        SELECT sap.account_id, COUNT(c.conversation_id) as conversation_count
        FROM sales_agent_profiles sap
        LEFT JOIN conversations c ON sap.account_id = c.agent_id AND c.status = 'Active'
        WHERE sap.status = 'Active'
        GROUP BY sap.account_id
        ORDER BY conversation_count ASC, sap.updated_at ASC
        LIMIT 1
    ");
	$stmt->execute();
	$agent = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($agent) {
		// Update customer information with assigned agent
		$updateStmt = $pdo->prepare("UPDATE customer_information SET agent_id = ? WHERE account_id = ?");
		$updateStmt->execute([$agent['account_id'], $customer_id]);

		return $agent['account_id'];
	}

	return null;
}

function createConversation($customer_id, $agent_id, $pdo)
{
	$stmt = $pdo->prepare("
        INSERT INTO conversations (customer_id, agent_id, status, last_message_at) 
        VALUES (?, ?, 'Active', NOW())
    ");
	$stmt->execute([$customer_id, $agent_id]);

	return $pdo->lastInsertId();
}

function getCustomerAgent($customer_id, $pdo)
{
	$stmt = $pdo->prepare("
        SELECT ci.agent_id 
        FROM customer_information ci 
        WHERE ci.account_id = ?
    ");
	$stmt->execute([$customer_id]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	return $result ? $result['agent_id'] : null;
}

function isAgentAvailable($agent_id, $pdo)
{
	$stmt = $pdo->prepare("
        SELECT status FROM sales_agent_profiles 
        WHERE account_id = ? AND status = 'Active'
    ");
	$stmt->execute([$agent_id]);

	return $stmt->fetch() !== false;
}
