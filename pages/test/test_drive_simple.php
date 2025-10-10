<?php
// Include the session initialization file
include_once('../../includes/init.php');

// Check database connection
if (!$pdo) {
	http_response_code(500);
	echo json_encode(['error' => 'Database connection not available']);
	exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	header('Content-Type: application/json');

	try {
		switch ($_POST['action']) {
			case 'approve_request':
				$request_id = $_POST['request_id'];
				$instructor = $_POST['instructor'] ?? '';
				$notes = $_POST['notes'] ?? '';

				$stmt = $pdo->prepare("
                    UPDATE test_drive_requests 
                    SET status = 'Approved', 
                        approved_at = NOW(), 
                        instructor_agent = ?, 
                        notes = ? 
                    WHERE id = ?
                ");
				$stmt->execute([$instructor, $notes, $request_id]);

				// --- Notification Logic ---
				require_once '../../includes/api/notification_api.php';
				$stmt2 = $pdo->prepare("SELECT account_id, selected_date FROM test_drive_requests WHERE id = ?");
				$stmt2->execute([$request_id]);
				$row = $stmt2->fetch(PDO::FETCH_ASSOC);
				if ($row && $row['account_id']) {
					createNotification($row['account_id'], null, 'Test Drive Approved', 'Your test drive request (ID: ' . $request_id . ') has been approved for ' . $row['selected_date'] . '.', 'test_drive', $request_id);
				}
				createNotification(null, 'Admin', 'Test Drive Approved', 'Test drive request (ID: ' . $request_id . ') has been approved.', 'test_drive', $request_id);
				// --- End Notification Logic ---

				echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
				exit();

			case 'reject_request':
				$request_id = $_POST['request_id'];
				$notes = $_POST['notes'] ?? '';

				$stmt = $pdo->prepare("
                    UPDATE test_drive_requests 
                    SET status = 'Rejected', 
                        notes = ? 
                    WHERE id = ?
                ");
				$stmt->execute([$notes, $request_id]);
				echo json_encode(['success' => true, 'message' => 'Request rejected']);
				exit();

			case 'complete_request':
				$request_id = $_POST['request_id'];
				$completion_notes = $_POST['completion_notes'] ?? '';

				$stmt = $pdo->prepare("
                    UPDATE test_drive_requests 
                    SET status = 'Completed', 
                        notes = CONCAT(COALESCE(notes, ''), '\nCompleted: ', ?) 
                    WHERE id = ?
                ");
				$stmt->execute([$completion_notes, $request_id]);
				echo json_encode(['success' => true, 'message' => 'Test drive marked as completed']);
				exit();

			case 'get_details':
				$request_id = $_POST['request_id'] ?? 0;

				if (!$request_id) {
					echo json_encode(['success' => false, 'message' => 'Request ID is required']);
					exit();
				}

				$stmt = $pdo->prepare("SELECT tdr.*, a.FirstName, a.LastName, a.Email,
                                     CASE WHEN tdr.drivers_license IS NOT NULL AND LENGTH(tdr.drivers_license) > 0 
                                          THEN 1 ELSE 0 END as has_license
                                     FROM test_drive_requests tdr 
                                     LEFT JOIN accounts a ON tdr.account_id = a.Id 
                                     WHERE tdr.id = ?");
				$stmt->execute([$request_id]);
				$result = $stmt->fetch(PDO::FETCH_ASSOC);

				if ($result) {
					// Enhance the data like in loan applications
					$enhancedResult = [
						'id' => $result['id'],
						'account_id' => $result['account_id'],
						'vehicle_id' => $result['vehicle_id'],
						'gate_pass_number' => $result['gate_pass_number'],
						'customer_name' => $result['customer_name'],
						'account_name' => trim(($result['FirstName'] ?? '') . ' ' . ($result['LastName'] ?? '')),
						'mobile_number' => $result['mobile_number'],
						'selected_date' => $result['selected_date'],
						'selected_time_slot' => $result['selected_time_slot'],
						'test_drive_location' => $result['test_drive_location'],
						'instructor_agent' => $result['instructor_agent'],
						'drivers_license' => $result['has_license'] == 1, // Check if license exists
						'status' => $result['status'],
						'terms_accepted' => $result['terms_accepted'],
						'requested_at' => $result['requested_at'],
						'approved_at' => $result['approved_at'],
						'notes' => $result['notes'],
						'email' => $result['Email']
					];

					echo json_encode(['success' => true, 'data' => $enhancedResult]);
				} else {
					echo json_encode(['success' => false, 'message' => 'Request not found']);
				}
				exit();

			default:
				echo json_encode(['success' => false, 'message' => 'Invalid action']);
				exit();
		}
	} catch (Exception $e) {
		error_log("Test Drive Error: " . $e->getMessage());
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		exit();
	}
}

// Simple data fetching from test_drive_requests table
$pending_requests = [];
$approved_requests = [];
$completed_requests = [];

try {
	// Fetch pending requests
	$stmt = $pdo->prepare("SELECT * FROM test_drive_requests WHERE status = 'Pending' ORDER BY requested_at DESC");
	$stmt->execute();
	$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Fetch approved requests
	$stmt = $pdo->prepare("SELECT * FROM test_drive_requests WHERE status = 'Approved' ORDER BY selected_date ASC");
	$stmt->execute();
	$approved_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Fetch completed requests
	$stmt = $pdo->prepare("SELECT * FROM test_drive_requests WHERE status = 'Completed' ORDER BY approved_at DESC");
	$stmt->execute();
	$completed_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$error_message = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test Drive Management - Simple Test</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			background-color: #f5f5f5;
		}

		.container {
			max-width: 1200px;
			margin: 0 auto;
			background: white;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		}

		.tab-buttons {
			display: flex;
			gap: 10px;
			margin-bottom: 20px;
		}

		.tab-button {
			padding: 10px 20px;
			background: #e0e0e0;
			border: none;
			cursor: pointer;
			border-radius: 5px;
		}

		.tab-button.active {
			background: #d60000;
			color: white;
		}

		.tab-content {
			display: none;
		}

		.tab-content.active {
			display: block;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 20px;
		}

		th,
		td {
			padding: 10px;
			text-align: left;
			border: 1px solid #ddd;
		}

		th {
			background-color: #f8f9fa;
			font-weight: bold;
		}

		.status {
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 12px;
			font-weight: bold;
		}

		.status.pending {
			background: #ffc107;
			color: #000;
		}

		.status.approved {
			background: #28a745;
			color: white;
		}

		.status.completed {
			background: #17a2b8;
			color: white;
		}

		.error {
			color: red;
			background: #ffe6e6;
			padding: 10px;
			border-radius: 5px;
			margin-bottom: 20px;
		}

		.stats {
			display: flex;
			gap: 20px;
			margin-bottom: 20px;
		}

		.stat-card {
			background: linear-gradient(45deg, #d60000, #ff4444);
			color: white;
			padding: 20px;
			border-radius: 8px;
			text-align: center;
			flex: 1;
		}

		.stat-number {
			font-size: 2em;
			font-weight: bold;
		}

		.stat-label {
			font-size: 0.9em;
			opacity: 0.9;
		}

		.action-btn {
			padding: 8px 15px;
			margin: 0 5px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 12px;
			font-weight: bold;
			text-decoration: none;
			display: inline-block;
			transition: all 0.3s ease;
		}

		.btn-primary {
			background: #007bff;
			color: white;
		}

		.btn-primary:hover {
			background: #0056b3;
		}

		.btn-success {
			background: #28a745;
			color: white;
		}

		.btn-success:hover {
			background: #1e7e34;
		}

		.btn-info {
			background: #17a2b8;
			color: white;
		}

		.btn-info:hover {
			background: #117a8b;
		}

		/* Enhanced Modal Styles to Match Dashboard Theme */
		.modal {
		    display: none;
		    position: fixed;
		    z-index: 1000;
		    left: 0;
		    top: 0;
		    width: 100vw;
		    height: 100vh;
		    background: rgba(30, 30, 30, 0.7);
		    backdrop-filter: blur(2px);
		    justify-content: center;
		    align-items: center;
		    transition: opacity 0.2s;
		  }
		  .modal.show {
		    display: flex;
		    opacity: 1;
		  }
		  .modal-content {
		    background: #181818;
		    color: #fff;
		    border-radius: 16px;
		    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
		    width: 95%;
		    max-width: 480px;
		    padding: 0;
		    overflow: hidden;
		    animation: modalPop 0.25s cubic-bezier(.4,2,.6,1) 1;
		  }
		  @keyframes modalPop {
		    0% { transform: scale(0.95); opacity: 0; }
		    100% { transform: scale(1); opacity: 1; }
		  }
		  .modal-header {
		    background: #b80000;
		    padding: 18px 28px 12px 28px;
		    display: flex;
		    align-items: center;
		    justify-content: space-between;
		    border-bottom: 1px solid #2d1b1b;
		  }
		  .modal-title {
		    font-size: 1.25rem;
		    font-weight: 600;
		    display: flex;
		    align-items: center;
		    gap: 10px;
		  }
		  .modal-close {
		    background: none;
		    border: none;
		    color: #fff;
		    font-size: 1.5rem;
		    cursor: pointer;
		    transition: color 0.2s;
		  }
		  .modal-close:hover {
		    color: #ffd700;
		  }
		  .modal-body {
		    padding: 24px 28px 18px 28px;
		    background: #222;
		  }
		  .modal-footer {
		    background: #191919;
		    padding: 16px 28px;
		    display: flex;
		    justify-content: flex-end;
		    border-top: 1px solid #2d1b1b;
		  }
		  .btn-close-modal {
		    background: #b80000;
		    color: #fff;
		    border: none;
		    border-radius: 8px;
		    padding: 8px 22px;
		    font-size: 1rem;
		    font-weight: 500;
		    cursor: pointer;
		    transition: background 0.2s;
		  }
		  .btn-close-modal:hover {
		    background: #8b0000;
		  }
		  .detail-section {
		    margin-bottom: 18px;
		    background: #232323;
		    border-radius: 8px;
		    padding: 14px 16px 10px 16px;
		    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
		  }
		  .detail-section h3 {
		    font-size: 1.08rem;
		    color: #ffd700;
		    margin-bottom: 10px;
		    font-weight: 600;
		    display: flex;
		    align-items: center;
		    gap: 7px;
		  }
		  .detail-row {
		    display: flex;
		    justify-content: space-between;
		    margin-bottom: 7px;
		    font-size: 0.97rem;
		  }
		  .detail-label {
		    color: #aaa;
		    font-weight: 500;
		    min-width: 110px;
		  }
		  .detail-value {
		    color: #fff;
		    font-weight: 400;
		    text-align: right;
		    flex: 1;
		  }
		  .btn-view-license {
		    background: #ffd700;
		    color: #181818;
		    border: none;
		    border-radius: 8px;
		    padding: 8px 18px;
		    font-size: 1rem;
		    font-weight: 600;
		    cursor: pointer;
		    margin-top: 10px;
		    transition: background 0.2s;
		  }
		  .btn-view-license:hover {
		    background: #ffe066;
		  }
		  @media (max-width: 600px) {
		    .modal-content { max-width: 98vw; }
		    .modal-header, .modal-body, .modal-footer { padding-left: 10px; padding-right: 10px; }
		  }
		</style>
</head>

<body>
	<div class="container">
		<h1><i class="fas fa-car"></i> Test Drive Management - Simple Test</h1>

		<?php if (isset($error_message)): ?>
			<div class="error"><?php echo htmlspecialchars($error_message); ?></div>
		<?php endif; ?>

		<div class="stats">
			<div class="stat-card">
				<div class="stat-number"><?php echo count($pending_requests); ?></div>
				<div class="stat-label">Pending Requests</div>
			</div>
			<div class="stat-card">
				<div class="stat-number"><?php echo count($approved_requests); ?></div>
				<div class="stat-label">Approved Bookings</div>
			</div>
			<div class="stat-card">
				<div class="stat-number"><?php echo count($completed_requests); ?></div>
				<div class="stat-label">Completed Drives</div>
			</div>
		</div>

		<div class="tab-buttons">
			<button class="tab-button active" onclick="showTab('pending')">
				<i class="fas fa-clock"></i> Pending Requests (<?php echo count($pending_requests); ?>)
			</button>
			<button class="tab-button" onclick="showTab('approved')">
				<i class="fas fa-check"></i> Approved Bookings (<?php echo count($approved_requests); ?>)
			</button>
			<button class="tab-button" onclick="showTab('completed')">
				<i class="fas fa-flag-checkered"></i> Completed Drives (<?php echo count($completed_requests); ?>)
			</button>
		</div>

		<!-- Pending Requests Tab -->
		<div id="pending" class="tab-content active">
			<h2>Pending Test Drive Requests</h2>
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Customer Name</th>
						<th>Mobile Number</th>
						<th>Selected Date</th>
						<th>Time Slot</th>
						<th>Location</th>
						<th>Status</th>
						<th>Requested At</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($pending_requests)): ?>
						<tr>
							<td colspan="9" style="text-align: center; color: #666;">No pending requests found</td>
						</tr>
					<?php else: ?>
						<?php foreach ($pending_requests as $request): ?>
							<tr>
								<td>TD-<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
								<td><?php echo htmlspecialchars($request['customer_name']); ?></td>
								<td><?php echo htmlspecialchars($request['mobile_number']); ?></td>
								<td><?php echo date('M d, Y', strtotime($request['selected_date'])); ?></td>
								<td><?php echo htmlspecialchars($request['selected_time_slot']); ?></td>
								<td><?php echo htmlspecialchars($request['test_drive_location']); ?></td>
								<td><span class="status pending"><?php echo $request['status']; ?></span></td>
								<td><?php echo date('M d, Y H:i', strtotime($request['requested_at'])); ?></td>
								<td>
									<button class="action-btn btn-primary" onclick="reviewRequest(<?php echo $request['id']; ?>)">
										<i class="fas fa-eye"></i> Review
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Approved Requests Tab -->
		<div id="approved" class="tab-content">
			<h2>Approved Test Drive Bookings</h2>
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Customer Name</th>
						<th>Mobile Number</th>
						<th>Scheduled Date</th>
						<th>Time Slot</th>
						<th>Location</th>
						<th>Instructor</th>
						<th>Approved At</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($approved_requests)): ?>
						<tr>
							<td colspan="9" style="text-align: center; color: #666;">No approved requests found</td>
						</tr>
					<?php else: ?>
						<?php foreach ($approved_requests as $request): ?>
							<tr>
								<td>TD-<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
								<td><?php echo htmlspecialchars($request['customer_name']); ?></td>
								<td><?php echo htmlspecialchars($request['mobile_number']); ?></td>
								<td><?php echo date('M d, Y', strtotime($request['selected_date'])); ?></td>
								<td><?php echo htmlspecialchars($request['selected_time_slot']); ?></td>
								<td><?php echo htmlspecialchars($request['test_drive_location']); ?></td>
								<td><?php echo htmlspecialchars($request['instructor_agent'] ?: 'Not assigned'); ?></td>
								<td><?php echo $request['approved_at'] ? date('M d, Y H:i', strtotime($request['approved_at'])) : 'N/A'; ?></td>
								<td>
									<button class="action-btn btn-success" onclick="markComplete(<?php echo $request['id']; ?>)">
										<i class="fas fa-check"></i> Complete
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Completed Requests Tab -->
		<div id="completed" class="tab-content">
			<h2>Completed Test Drives</h2>
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Customer Name</th>
						<th>Mobile Number</th>
						<th>Drive Date</th>
						<th>Time Slot</th>
						<th>Location</th>
						<th>Instructor</th>
						<th>Notes</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($completed_requests)): ?>
						<tr>
							<td colspan="9" style="text-align: center; color: #666;">No completed drives found</td>
						</tr>
					<?php else: ?>
						<?php foreach ($completed_requests as $request): ?>
							<tr>
								<td>TD-<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
								<td><?php echo htmlspecialchars($request['customer_name']); ?></td>
								<td><?php echo htmlspecialchars($request['mobile_number']); ?></td>
								<td><?php echo date('M d, Y', strtotime($request['selected_date'])); ?></td>
								<td><?php echo htmlspecialchars($request['selected_time_slot']); ?></td>
								<td><?php echo htmlspecialchars($request['test_drive_location']); ?></td>
								<td><?php echo htmlspecialchars($request['instructor_agent'] ?: 'N/A'); ?></td>
								<td><?php echo htmlspecialchars(substr($request['notes'] ?: 'No notes', 0, 50) . (strlen($request['notes'] ?: '') > 50 ? '...' : '')); ?></td>
								<td>
									<button class="action-btn btn-info" onclick="viewDetails(<?php echo $request['id']; ?>)">
										<i class="fas fa-info-circle"></i> Details
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Test Drive Details Modal -->
	<div id="detailsModal" class="modal">
		<div class="modal-content">
			<div class="modal-header">
				<div class="modal-title">
					<i class="fas fa-car"></i>
					<span id="modalTitle">Test Drive Details</span>
				</div>
				<button class="modal-close" onclick="closeDetailsModal()">&times;</button>
			</div>
			<div class="modal-body" id="modalBody">
				<!-- Details will be loaded here -->
			</div>
			<div class="modal-footer">
				<button class="btn-close-modal" onclick="closeDetailsModal()">Close</button>
			</div>
		</div>
	</div>

	<!-- License Viewer Modal -->
	<div id="licenseModal" class="license-modal">
		<div class="license-modal-content">
			<button class="license-modal-close" onclick="closeLicenseModal()">&times;</button>
			<img id="licenseImage" class="license-image" src="" alt="Driver's License">
		</div>
	</div>

	<script>
		function showTab(tabName) {
			// Hide all tab contents
			const tabContents = document.querySelectorAll('.tab-content');
			tabContents.forEach(content => content.classList.remove('active'));

			// Remove active class from all buttons
			const tabButtons = document.querySelectorAll('.tab-button');
			tabButtons.forEach(button => button.classList.remove('active'));

			// Show selected tab content
			document.getElementById(tabName).classList.add('active');

			// Add active class to clicked button
			event.target.classList.add('active');
		}

		function reviewRequest(requestId) {
			Swal.fire({
				title: 'Review Test Drive Request',
				html: `
					<div style="text-align: left;">
						<div style="margin-bottom: 15px;">
							<label style="display: block; margin-bottom: 5px; font-weight: bold;">Action:</label>
							<select id="reviewAction" class="swal2-select" style="width: 100%;">
								<option value="">Select action</option>
								<option value="approve">Approve Request</option>
								<option value="reject">Reject Request</option>
							</select>
						</div>
						<div id="instructorField" style="margin-bottom: 15px; display: none;">
							<label style="display: block; margin-bottom: 5px; font-weight: bold;">Assign Instructor:</label>
							<input id="instructor" type="text" class="swal2-input" placeholder="Enter instructor name" style="margin: 0;">
						</div>
						<div style="margin-bottom: 15px;">
							<label style="display: block; margin-bottom: 5px; font-weight: bold;">Notes:</label>
							<textarea id="reviewNotes" class="swal2-textarea" placeholder="Add any notes or comments" style="margin: 0;"></textarea>
						</div>
					</div>
				`,
				showCancelButton: true,
				confirmButtonText: 'Submit',
				cancelButtonText: 'Cancel',
				preConfirm: () => {
					const action = document.getElementById('reviewAction').value;
					const instructor = document.getElementById('instructor').value;
					const notes = document.getElementById('reviewNotes').value;

					if (!action) {
						Swal.showValidationMessage('Please select an action');
						return false;
					}

					return {
						action,
						instructor,
						notes
					};
				}
			}).then((result) => {
				if (result.isConfirmed) {
					const {
						action,
						instructor,
						notes
					} = result.value;

					const formData = new FormData();
					formData.append('action', action === 'approve' ? 'approve_request' : 'reject_request');
					formData.append('request_id', requestId);
					formData.append('notes', notes);
					if (action === 'approve') {
						formData.append('instructor', instructor);
					}

					fetch('', {
							method: 'POST',
							body: formData
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								Swal.fire('Success!', data.message, 'success').then(() => {
									location.reload(); // Refresh page to see updated data
								});
							} else {
								Swal.fire('Error!', data.message, 'error');
							}
						})
						.catch(error => {
							Swal.fire('Error!', 'An error occurred while processing the request', 'error');
						});
				}
			});

			// Show/hide instructor field based on action
			setTimeout(() => {
				document.getElementById('reviewAction').addEventListener('change', function() {
					const instructorField = document.getElementById('instructorField');
					if (this.value === 'approve') {
						instructorField.style.display = 'block';
					} else {
						instructorField.style.display = 'none';
					}
				});
			}, 100);
		}

		function markComplete(requestId) {
			Swal.fire({
				title: 'Mark Test Drive as Completed',
				html: `
					<div style="text-align: left;">
						<label style="display: block; margin-bottom: 5px; font-weight: bold;">Completion Notes:</label>
						<textarea id="completionNotes" class="swal2-textarea" placeholder="Add completion notes, feedback, or observations"></textarea>
					</div>
				`,
				showCancelButton: true,
				confirmButtonText: 'Mark Complete',
				cancelButtonText: 'Cancel',
				preConfirm: () => {
					return document.getElementById('completionNotes').value;
				}
			}).then((result) => {
				if (result.isConfirmed) {
					const formData = new FormData();
					formData.append('action', 'complete_request');
					formData.append('request_id', requestId);
					formData.append('completion_notes', result.value);

					fetch('', {
							method: 'POST',
							body: formData
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								Swal.fire('Success!', data.message, 'success').then(() => {
									location.reload(); // Refresh page to see updated data
								});
							} else {
								Swal.fire('Error!', data.message, 'error');
							}
						})
						.catch(error => {
							Swal.fire('Error!', 'An error occurred while processing the request', 'error');
						});
				}
			});
		}

		function viewDetails(requestId) {
			console.log('Viewing details for request ID:', requestId);

			// Fetch detailed information about the completed test drive
			const formData = new FormData();
			formData.append('action', 'get_details');
			formData.append('request_id', requestId);

			fetch('', {
					method: 'POST',
					body: formData
				})
				.then(response => {
					console.log('Response status:', response.status);
					return response.json();
				})
				.then(data => {
					console.log('Response data:', data);
					if (data.success) {
						const request = data.data;
						const customerName = request.account_name && request.account_name.trim() !== '' ?
							request.account_name :
							request.customer_name;

						// Update modal title
						document.getElementById('modalTitle').textContent = `Test Drive Details - TD-${String(request.id).padStart(4, '0')}`;

						// Build modal content
						let modalContent = `
							<div class="detail-section">
								<h3><i class="fas fa-user"></i> Customer Information</h3>
								<div class="detail-row">
									<div class="detail-label">Account ID:</div>
									<div class="detail-value">${request.account_id || 'N/A'}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Name:</div>
									<div class="detail-value">${customerName}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Email:</div>
									<div class="detail-value">${request.email || 'N/A'}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Mobile:</div>
									<div class="detail-value">${request.mobile_number}</div>
								</div>
							</div>

							<div class="detail-section">
								<h3><i class="fas fa-car-side"></i> Vehicle Information</h3>
								<div class="detail-row">
									<div class="detail-label">Vehicle ID:</div>
									<div class="detail-value">${request.vehicle_id || 'Not specified'}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Gate Pass:</div>
									<div class="detail-value">${request.gate_pass_number || 'Not assigned'}</div>
								</div>
							</div>

							<div class="detail-section">
								<h3><i class="fas fa-calendar-check"></i> Test Drive Information</h3>
								<div class="detail-row">
									<div class="detail-label">Date:</div>
									<div class="detail-value">${new Date(request.selected_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Time:</div>
									<div class="detail-value">${request.selected_time_slot}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Location:</div>
									<div class="detail-value">${request.test_drive_location}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Instructor:</div>
									<div class="detail-value">${request.instructor_agent || 'Not assigned'}</div>
								</div>
							</div>

							<div class="detail-section">
								<h3><i class="fas fa-file-alt"></i> Documentation</h3>
								<div class="detail-row">
									<div class="detail-label">Driver's License:</div>
									<div class="detail-value">${request.drivers_license ? '<span style="color: green;"><i class="fas fa-check-circle"></i> Uploaded</span>' : '<span style="color: #999;"><i class="fas fa-times-circle"></i> Not uploaded</span>'}</div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Terms Accepted:</div>
									<div class="detail-value">${request.terms_accepted == 1 ? '<span style="color: green;"><i class="fas fa-check-circle"></i> Yes</span>' : '<span style="color: red;"><i class="fas fa-times-circle"></i> No</span>'}</div>
								</div>
							</div>

							<div class="detail-section">
								<h3><i class="fas fa-info-circle"></i> Status & Dates</h3>
								<div class="detail-row">
									<div class="detail-label">Status:</div>
									<div class="detail-value"><span class="status ${request.status.toLowerCase()}">${request.status}</span></div>
								</div>
								<div class="detail-row">
									<div class="detail-label">Requested:</div>
									<div class="detail-value">${new Date(request.requested_at).toLocaleString()}</div>
								</div>
								${request.approved_at ? `
								<div class="detail-row">
									<div class="detail-label">Approved:</div>
									<div class="detail-value">${new Date(request.approved_at).toLocaleString()}</div>
								</div>
								` : ''}
							</div>

							<div class="detail-section">
								<h3><i class="fas fa-sticky-note"></i> Notes</h3>
								<div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
									${request.notes || '<em style="color: #999;">No notes available</em>'}
								</div>
							</div>
						`;

						// Add view license button if available
						if (request.drivers_license) {
							modalContent += `
								<div class="detail-section">
									<button class="btn-view-license" onclick="viewDriversLicense(${request.id})">
										<i class="fas fa-id-card"></i> View Driver's License
									</button>
								</div>
							`;
						}

						// Set modal content and show modal
						document.getElementById('modalBody').innerHTML = modalContent;
						showDetailsModal();
					} else {
						console.error('API Error:', data.message);
						Swal.fire('Error!', data.message || 'Failed to fetch details', 'error');
					}
				})
				.catch(error => {
					console.error('Fetch error:', error);
					Swal.fire('Error!', 'An error occurred while fetching details', 'error');
				});
		}

		function showDetailsModal() {
			const modal = document.getElementById('detailsModal');
			modal.classList.add('show');
			document.body.style.overflow = 'hidden'; // Prevent background scrolling
		}

		function closeDetailsModal() {
			const modal = document.getElementById('detailsModal');
			modal.classList.remove('show');
			document.body.style.overflow = ''; // Restore scrolling
		}

		function viewDriversLicense(requestId) {
			// Show the license in a modal instead of new window
			const licenseModal = document.getElementById('licenseModal');
			const licenseImage = document.getElementById('licenseImage');

			// Set the image source to the view_license.php endpoint
			licenseImage.src = `view_license.php?id=${requestId}&t=${Date.now()}`; // Add timestamp to prevent caching

			// Show the modal
			licenseModal.classList.add('show');
			document.body.style.overflow = 'hidden';
		}

		function closeLicenseModal() {
			const licenseModal = document.getElementById('licenseModal');
			licenseModal.classList.remove('show');
			document.body.style.overflow = '';
		}

		// Close modal when clicking outside
		window.onclick = function(event) {
			const modal = document.getElementById('detailsModal');
			if (event.target === modal) {
				closeDetailsModal();
			}
		}

		// Close license modal when clicking outside
		document.getElementById('licenseModal').addEventListener('click', function(event) {
			if (event.target === this) {
				closeLicenseModal();
			}
		});
	</script>
</body>

</html>