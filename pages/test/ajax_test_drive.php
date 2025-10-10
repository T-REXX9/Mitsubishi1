<?php
// Simple AJAX test for test drive management
include_once('../../includes/init.php');

// Check if user is logged in
if (!isLoggedIn()) {
	die('Not logged in');
}

?>
<!DOCTYPE html>
<html>

<head>
	<title>Test Drive AJAX Test</title>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
	<h1>Test Drive AJAX Handler Test</h1>
	<p>Current User Role: <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
	<p>Current User ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>

	<button onclick="testGetDetails()">Test Get Details (ID: 1)</button>
	<button onclick="testApprove()">Test Approve (ID: 1)</button>

	<div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;">
		<h3>Result:</h3>
		<pre id="resultContent">No test run yet</pre>
	</div>

	<script>
		function testGetDetails() {
			const formData = new FormData();
			formData.append('action', 'get_details');
			formData.append('request_id', '1');

			fetch('../main/dashboard.php', {
					method: 'POST',
					body: formData
				})
				.then(response => {
					console.log('Response status:', response.status);
					console.log('Response headers:', response.headers);
					return response.text(); // Get as text first to debug
				})
				.then(text => {
					console.log('Raw response:', text);
					document.getElementById('resultContent').textContent = text;

					// Try to parse as JSON
					try {
						const data = JSON.parse(text);
						console.log('Parsed data:', data);

						if (data.success) {
							Swal.fire('Success', 'Data retrieved successfully', 'success');
						} else {
							Swal.fire('Error', data.message || 'Unknown error', 'error');
						}
					} catch (e) {
						console.error('JSON parse error:', e);
						Swal.fire('Error', 'Response is not valid JSON', 'error');
					}
				})
				.catch(error => {
					console.error('Fetch error:', error);
					document.getElementById('resultContent').textContent = 'Fetch error: ' + error.message;
				});
		}

		function testApprove() {
			const formData = new FormData();
			formData.append('action', 'approve_request');
			formData.append('request_id', '1');
			formData.append('instructor', 'Test Instructor');
			formData.append('notes', 'Test approval');

			fetch('../main/dashboard.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.text())
				.then(text => {
					console.log('Raw response:', text);
					document.getElementById('resultContent').textContent = text;

					try {
						const data = JSON.parse(text);
						if (data.success) {
							Swal.fire('Success', data.message, 'success');
						} else {
							Swal.fire('Error', data.message, 'error');
						}
					} catch (e) {
						Swal.fire('Error', 'Invalid response', 'error');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					document.getElementById('resultContent').textContent = 'Error: ' + error.message;
				});
		}
	</script>
</body>

</html>