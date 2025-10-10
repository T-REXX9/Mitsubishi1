<?php
// Simple session test
session_start();

echo "Session Test Results:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";

// Try to set and retrieve session data
$_SESSION['test_key'] = 'test_value';
echo "Set test_key = test_value\n";
echo "Retrieved: " . $_SESSION['test_key'] . "\n";
?>